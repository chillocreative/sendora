<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
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
     * Toggle conversation between AI-active and paused states.
     * Users can only pause or resume the AI — no human takeover.
     */
    public function toggleMode(Request $request, $id)
    {
        $conversation = Conversation::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'status' => 'required|in:active,paused,closed',
        ]);

        $conversation->update(['status' => $request->status]);

        return back()->with('success', 'Conversation updated.');
    }
}
