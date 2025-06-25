@component('mail::message')
# Account suspended

Hello, {{ $user->name }},

Your account has been suspended by the administrator. If you have any questions, please contact the support team.

Thank you!
@endcomponent
