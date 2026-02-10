<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $conversations = Conversation::where('user_id', $user->id)
            ->with(['whatsappNumber:id,phone_number'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->whatsapp_number_id, fn ($q, $id) => $q->where('whatsapp_number_id', $id))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('contact_phone', 'like', "%{$search}%")
                      ->orWhere('contact_name', 'like', "%{$search}%");
                });
            })
            ->latest('last_customer_message_at')
            ->paginate(20);

        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations,
            'whatsappNumbers' => $user->whatsappNumbers()->select('id', 'phone_number', 'status')->get(),
            'filters' => $request->only(['status', 'whatsapp_number_id', 'search']),
        ]);
    }

    public function show($id)
    {
        $conversation = Conversation::where('user_id', auth()->id())
            ->with(['whatsappNumber:id,phone_number,status,playbook_id', 'messages'])
            ->findOrFail($id);

        return Inertia::render('Conversations/Show', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Toggle conversation between AI-handled and human-handled.
     */
    public function toggleMode(Request $request, $id)
    {
        $conversation = Conversation::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'status' => 'required|in:active,escalated,closed',
        ]);

        $updateData = ['status' => $request->status];

        if ($request->status === 'escalated') {
            $updateData['escalation_reason'] = 'Manual takeover by user';
            $updateData['escalated_at'] = now();
        } elseif ($request->status === 'active') {
            $updateData['escalation_reason'] = null;
            $updateData['escalated_at'] = null;
        }

        $conversation->update($updateData);

        return back()->with('success', 'Conversation mode updated.');
    }

    /**
     * Send a manual reply as a human operator.
     */
    public function sendReply(Request $request, $id)
    {
        $conversation = Conversation::where('user_id', auth()->id())
            ->with('whatsappNumber')
            ->findOrFail($id);

        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        if (!$conversation->isWithin24HourWindow()) {
            return back()->with('error', 'Cannot reply: customer has not messaged in the last 24 hours.');
        }

        $whatsappNumber = $conversation->whatsappNumber;

        if (!$whatsappNumber || $whatsappNumber->status !== 'connected') {
            return back()->with('error', 'WhatsApp number is not connected.');
        }

        $whatsappService = new WhatsappService();
        $sendResult = $whatsappService->sendMessage(
            $whatsappNumber,
            $conversation->contact_phone,
            $request->message
        );

        $waMessageId = null;
        if ($sendResult && $sendResult->successful()) {
            $waMessageId = $sendResult->json('message_id');
        }

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender_type' => 'human',
            'body' => $request->message,
            'wa_message_id' => $waMessageId,
        ]);

        $conversation->update([
            'message_count' => $conversation->message_count + 1,
        ]);

        return back()->with('success', 'Message sent.');
    }
}
