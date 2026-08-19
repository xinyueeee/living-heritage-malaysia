<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PersonalizedAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $experience;

    public function __construct($experience)
    {
        $this->experience = $experience;
    }

    public function build()
    {
        return $this->subject('Upcoming Experience Alert')
            ->view('festival.personalized-alert');
    }
}