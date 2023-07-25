<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Otpmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        //
        $this->details = $details;
     
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            return $this->view('emails.OTPpassword')->subject(get_constant('SITE_NAME') . " -  password mail")->with("details", $this->details);
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
        }

    }
}
