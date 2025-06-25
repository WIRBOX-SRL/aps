@component('mail::message')
# Your subscription is expiring soon

Hello, {{ $user->name }},

Your subscription **{{ $subscription->name }}** will expire in **{{ $daysUntilExpiry }} days** ({{ $subscription->ends_at->format('d.m.Y') }}).

To continue enjoying our services, please renew your subscription.

@component('mail::button', ['url' => config('app.url') . '/admin'])
Access the admin panel
@endcomponent

If you have any questions, feel free to contact us.

Thank you!
@endcomponent
