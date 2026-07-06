<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAccountVerification extends Mailable
{
    use Queueable, SerializesModels;

    // Public properties are automatically available in your Blade view
    public $userFirstName;
    public $userEmail;
    public $actionLink;
    public $appName;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $token)
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:8080');
        
        $this->userFirstName = $user->first_name;
        $this->userEmail = $user->email;
        $this->actionLink = $frontendUrl . '/verify-account/' . $token;
        $this->appName = config('app.name');
    }

    /**
     * Get the message envelope (Subject, From, etc.)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Account - ' . config('app.name'),
            // "from" is automatically pulled from your .env config/mail.php, 
            // but you can explicitly override it here if needed.
        );
    }

    /**
     * Get the message content definition (The Blade Template)
     */
    public function content(): Content
    {
        return new Content(
            view: 'user-verify-account-temp',
        );
    }
}
