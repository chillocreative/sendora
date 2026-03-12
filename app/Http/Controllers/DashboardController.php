<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Playbook;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription()->with('plan')->first()
            ?? $user->latestSubscription()->with('plan')->first();

        // Upcoming reminders
        $upcomingReminders = Reminder::where('user_id', $user->id)
            ->upcoming()
            ->take(5)
            ->get(['id', 'title', 'reminder_at', 'event_at', 'location', 'status', 'source']);

        // Reminder stats
        $remindersThisMonth = Reminder::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $remindersSentToday = Reminder::where('user_id', $user->id)
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();

        $remindersSentThisMonth = Reminder::where('user_id', $user->id)
            ->where('status', 'sent')
            ->whereYear('sent_at', now()->year)
            ->whereMonth('sent_at', now()->month)
            ->count();

        $reminderLimit = $subscription?->plan?->limits['reminders_per_month'] ?? 0;

        // Recent deliveries
        $recentDeliveries = Reminder::where('user_id', $user->id)
            ->whereIn('status', ['sent', 'failed'])
            ->orderByDesc('sent_at')
            ->take(10)
            ->get(['id', 'title', 'status', 'sent_at', 'error_message', 'source']);

        // Calendar connection status
        $calendarConnected = $user->googleCalendarConnection !== null;

        // AI Playbook stats
        $aiStats = $this->getAiStats($user);

        return Inertia::render('Dashboard', [
            'subscription' => $subscription,
            'whatsappCount' => $user->whatsappNumbers()->count(),
            'upcomingReminders' => $upcomingReminders,
            'reminderStats' => [
                'sent_today' => $remindersSentToday,
                'sent_this_month' => $remindersSentThisMonth,
                'created_this_month' => $remindersThisMonth,
                'plan_limit' => $reminderLimit,
            ],
            'recentDeliveries' => $recentDeliveries,
            'calendarConnected' => $calendarConnected,
            'aiStats' => $aiStats,
        ]);
    }

    protected function getAiStats($user): array
    {
        $waNumberIds = $user->whatsappNumbers()->pluck('id');

        $activePlaybooks = Playbook::where('user_id', $user->id)->where('is_active', true)->count();
        $totalPlaybooks = Playbook::where('user_id', $user->id)->count();

        $totalConversations = Conversation::whereIn('whatsapp_number_id', $waNumberIds)->count();
        $activeConversations = Conversation::whereIn('whatsapp_number_id', $waNumberIds)->where('status', 'active')->count();
        $escalatedConversations = Conversation::whereIn('whatsapp_number_id', $waNumberIds)->where('status', 'escalated')->count();

        $aiRepliesToday = ConversationMessage::whereIn('conversation_id',
                Conversation::whereIn('whatsapp_number_id', $waNumberIds)->pluck('id')
            )
            ->where('sender_type', 'ai')
            ->where('direction', 'outbound')
            ->whereDate('created_at', Carbon::today())
            ->count();

        $aiRepliesThisMonth = ConversationMessage::whereIn('conversation_id',
                Conversation::whereIn('whatsapp_number_id', $waNumberIds)->pluck('id')
            )
            ->where('sender_type', 'ai')
            ->where('direction', 'outbound')
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $numbersWithAi = $user->whatsappNumbers()->where('ai_reply_enabled', true)->whereNotNull('playbook_id')->count();

        $recentConversations = Conversation::whereIn('whatsapp_number_id', $waNumberIds)
            ->whereIn('status', ['active', 'escalated'])
            ->orderByDesc('updated_at')
            ->take(5)
            ->get(['id', 'contact_phone', 'contact_name', 'status', 'message_count', 'last_customer_message_at', 'escalation_reason']);

        return [
            'active_playbooks' => $activePlaybooks,
            'total_playbooks' => $totalPlaybooks,
            'total_conversations' => $totalConversations,
            'active_conversations' => $activeConversations,
            'escalated_conversations' => $escalatedConversations,
            'ai_replies_today' => $aiRepliesToday,
            'ai_replies_this_month' => $aiRepliesThisMonth,
            'numbers_with_ai' => $numbersWithAi,
            'recent_conversations' => $recentConversations,
        ];
    }
}
