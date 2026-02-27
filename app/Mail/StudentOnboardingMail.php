<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentOnboardingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $schoolEmail,
        public string $activationUrl,
        public int $expiresInHours
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your Starlight Student Account')
            ->view('emails.student-onboarding')
            ->text('emails.student-onboarding-text');
    }
}
