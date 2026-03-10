@component('mail::message')
    # Successful Registration

    Hello **{{ $user->name }}**,

    @component('mail::panel')
        Your registration was successful.
    @endcomponent

    @component('mail::button', ['url' => config('app.url')])
        Visit {{ config('app.name') }}
    @endcomponent

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
