@component('mail::message')
    # Password reset link

    Hello **{{ $userName }}**,

    Click on the this link to reset your password
    @component('mail::button', ['url' => $url])
        Reset
    @endcomponent

    The link will be expired {{$expiry}} minutes after this mail been sent.

    If you didn't request this, no action is needed.

    Regards, {{ $appName }}
@endcomponent
