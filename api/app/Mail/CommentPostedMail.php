<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentPostedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comment $comment
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Comment on Your Post',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comment-posted',
            with: [
                'comment' => $this->comment,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
