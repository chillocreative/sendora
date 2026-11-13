<x-mail::message>
# {{ $remaining > 0 ? 'Your AI tokens are running low' : 'You have used all your AI tokens' }}

Hi {{ $name }},

@if ($remaining > 0)
You have **{{ number_format($remaining) }}** AI tokens left, out of a monthly allowance of {{ number_format($allowance) }}.
@else
You have used your monthly allowance of {{ number_format($allowance) }} AI tokens and any purchased packs.
@endif

Reminders sent as plain commands keep working, but AI features (understanding free-text messages, images and documents, and auto-replies) pause until your allowance resets next month or you add more tokens.

<x-mail::button :url="config('app.url') . '/subscription'">
Buy more tokens
</x-mail::button>

Best Regards,<br>
The Sendora Team
</x-mail::message>
