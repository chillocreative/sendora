<x-mail::message>
@if($type === 'due')
# Upcoming Reminder
@else
# Reminder Created
@endif

**{{ $reminder->title }}**

@php
$dateTime = $reminder->event_at ?? $reminder->reminder_at;
@endphp

**When:** {{ $dateTime->format('l, d M Y \a\t g:i A') }}

@if($reminder->location)
**Where:** {{ $reminder->location }}
@endif

@if($reminder->description)
**Details:** {{ $reminder->description }}
@endif

@if($type === 'due')
This is a reminder that your event is coming up soon.
@else
A reminder has been set and you will be notified before the event.
@endif

<x-mail::button :url="config('app.url') . '/reminders'">
View in Dashboard
</x-mail::button>

Best Regards,<br>
The Sendora Team
</x-mail::message>
