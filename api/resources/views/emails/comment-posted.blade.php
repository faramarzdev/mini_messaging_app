@component('mail::message')
    # New Comment on Your Post

    Hello **{{ $comment->post->user->name }}**,

    {{ $comment->user->name }} commented on your post **"{{ $comment->post->title }}"**:

    @component('mail::panel')
        {{ $comment->content }}
    @endcomponent

    @component('mail::button', ['url' => config('app.url') . '/posts/' . $comment->post_id])
        View Post
    @endcomponent

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
