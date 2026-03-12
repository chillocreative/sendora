<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReminderController extends Controller
{
    public function __construct(
        protected ReminderService $reminderService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $filter = $request->input('filter', 'upcoming');

        $query = Reminder::where('user_id', $user->id);

        $query = match ($filter) {
            'upcoming' => $query->where('status', 'pending')->where('reminder_at', '>', now())->orderBy('reminder_at'),
            'past' => $query->whereIn('status', ['sent', 'failed', 'cancelled'])->orderByDesc('reminder_at'),
            'all' => $query->orderByDesc('created_at'),
            default => $query->where('status', 'pending')->orderBy('reminder_at'),
        };

        $reminders = $query->with('whatsappNumber:id,phone_number')->paginate(20);

        return Inertia::render('Reminders/Index', [
            'reminders' => $reminders,
            'filter' => $filter,
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        return Inertia::render('Reminders/Create', [
            'whatsappNumbers' => $user->whatsappNumbers()
                ->where('status', 'connected')
                ->get(['id', 'phone_number']),
            'hasCalendar' => $user->googleCalendarConnection !== null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'event_at' => 'required|date|after:now',
            'minutes_before' => 'required|integer|min:0|max:10080',
            'location' => 'nullable|string|max:255',
            'recurrence_rule' => 'nullable|in:daily,weekly,monthly,yearly',
            'whatsapp_number_id' => 'nullable|exists:whatsapp_numbers,id',
            'add_to_calendar' => 'boolean',
        ]);

        $user = auth()->user();

        // Check reminder limit
        $plan = $user->current_plan;
        $limit = $plan?->limits['reminders_per_month'] ?? 0;
        if ($limit > 0) {
            $usedThisMonth = Reminder::where('user_id', $user->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
            if ($usedThisMonth >= $limit) {
                return back()->with('error', "You've reached your monthly reminder limit ({$limit}). Please upgrade your plan.");
            }
        }

        $validated['source'] = 'web';
        $this->reminderService->createReminder($user, $validated);

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder created successfully!');
    }

    public function edit(int $id)
    {
        $reminder = Reminder::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        return Inertia::render('Reminders/Edit', [
            'reminder' => $reminder,
            'whatsappNumbers' => auth()->user()->whatsappNumbers()
                ->where('status', 'connected')
                ->get(['id', 'phone_number']),
            'hasCalendar' => auth()->user()->googleCalendarConnection !== null,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $reminder = Reminder::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'event_at' => 'required|date|after:now',
            'minutes_before' => 'required|integer|min:0|max:10080',
            'location' => 'nullable|string|max:255',
            'recurrence_rule' => 'nullable|in:daily,weekly,monthly,yearly',
            'whatsapp_number_id' => 'nullable|exists:whatsapp_numbers,id',
        ]);

        $eventAt = \Carbon\Carbon::parse($validated['event_at']);
        $validated['reminder_at'] = $eventAt->copy()->subMinutes($validated['minutes_before']);

        $reminder->update($validated);

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder updated!');
    }

    public function destroy(int $id)
    {
        $reminder = Reminder::where('user_id', auth()->id())->findOrFail($id);

        if ($reminder->status === 'pending') {
            $reminder->update(['status' => 'cancelled']);
        } else {
            $reminder->delete();
        }

        return back()->with('success', 'Reminder cancelled.');
    }
}
