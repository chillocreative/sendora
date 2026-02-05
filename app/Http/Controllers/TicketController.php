<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()
            ->withCount(['unreadAdminReplies as unread_count'])
            ->with('latestReply')
            ->latest()
            ->paginate(15);

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tickets/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => 'required|in:low,medium,high,urgent',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,pdf',
        ]);

        $user = $request->user();

        $ticket = $user->tickets()->create([
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
        ]);

        // Create initial reply with the description
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                $attachments[] = $path;
            }
        }

        $ticket->replies()->create([
            'user_id' => $user->id,
            'message' => $request->description,
            'attachments' => !empty($attachments) ? $attachments : null,
            'is_admin' => false,
        ]);

        // Send WhatsApp notification to admin
        $this->notifyAdmin($ticket, $user);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Request $request, $id)
    {
        $ticket = $request->user()->tickets()->findOrFail($id);

        // Mark admin replies as read
        $ticket->replies()
            ->where('is_admin', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $ticket->load(['replies.user']);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,pdf',
        ]);

        $ticket = $request->user()->tickets()->findOrFail($id);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                $attachments[] = $path;
            }
        }

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'attachments' => !empty($attachments) ? $attachments : null,
            'is_admin' => false,
        ]);

        // Reopen ticket if it was resolved/closed
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent.');
    }

    private function notifyAdmin(Ticket $ticket, User $user)
    {
        try {
            // Find admin user
            $admin = User::where('email', 'admin@blaster.com')->first();
            if (!$admin) {
                Log::warning('Ticket notification: admin user not found');
                return;
            }

            // Find admin's connected WhatsApp device
            $device = $admin->whatsappNumbers()->where('status', 'connected')->first();
            if (!$device) {
                Log::warning('Ticket notification: no connected WhatsApp device for admin');
                return;
            }

            // Get admin mobile number from settings, fallback to device phone number (self-send)
            $adminMobile = Setting::where('key', 'admin_mobile_number')->value('value');
            if (!$adminMobile) {
                $adminMobile = $device->phone_number;
            }

            if (!$adminMobile) {
                Log::warning('Ticket notification: no admin mobile number available');
                return;
            }

            $priorityLabel = strtoupper($ticket->priority);
            $message = "[SENDORA TICKET #{$ticket->id}]\n\n"
                . "New support ticket from {$user->name}\n"
                . "Subject: {$ticket->subject}\n"
                . "Priority: {$priorityLabel}\n\n"
                . substr($ticket->description, 0, 200)
                . (strlen($ticket->description) > 200 ? '...' : '');

            $whatsappService = new WhatsappService();
            $response = $whatsappService->sendMessage($device, $adminMobile, $message);

            Log::info('Ticket notification sent', [
                'ticket_id' => $ticket->id,
                'to' => $adminMobile,
                'device_id' => $device->id,
                'response' => $response ? 'success' : 'failed',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send ticket WhatsApp notification', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
