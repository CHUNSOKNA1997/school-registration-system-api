<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class StudentOnboardingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $schoolEmail,
        public string $activationUrl,
        public int $expiresInHours
    ) {
        $this->afterCommit();
    }

    public function build(): self
    {
        return $this
            ->subject('Your Starlight Student Account')
            ->view('emails.student-onboarding')
            ->text('emails.student-onboarding-text');
    }
}
