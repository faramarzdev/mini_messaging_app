@component('mail::message')
    # Successful Registration

    Hello **{{ $userName }}**,

    @component('mail::panel')
        Your registration was successful.
    @endcomponent

    @component('mail::button', ['url' => $appUrl])
        Visit {{ $appName }}
    @endcomponent


    Regards, {{ $appName }}
@endcomponent
