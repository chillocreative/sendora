<x-mail::message>
@if($type === 'due')
# Upcoming Reminder
@elseif($type === 'failed')
# Reminder Not Delivered
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
@elseif($type === 'failed')
We could not deliver this reminder on WhatsApp.

**Reason:** {{ $reason ?? 'Unknown error.' }}
@else
A reminder has been set and you will be notified before the event.
@endif

<x-mail::button :url="config('app.url') . '/reminders'">
View in Dashboard
</x-mail::button>

Best Regards,<br>
The Sendora Team
</x-mail::message>
