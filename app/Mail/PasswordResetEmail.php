<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $actionLink;
    public $appName;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $token)
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:8080');
        
        $this->user = $user;
        $this->appName = config('app.name');
        $this->actionLink = "{$frontendUrl}/password-reset/{$token}?email=" . urlencode($user->email);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'user-forgot-email-temp',
        );
    }
}
