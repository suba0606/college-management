<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Exception;

class ResetPasswordMail extends Mailable {

    use Queueable,
        SerializesModels;

    protected $details;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details) {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        try{
            return $this->view('emails.ResetPassword')->subject(get_constant('SITE_NAME')." - Reset password mail")->with("details", $this->details);
        } catch (Exception $ex) {
            report($ex);
        }
        
    }

}
