<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\ResetPasswordMail;
use Mail;
use Exception;

class ResetpasswordJob implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details) {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {

        try {
            $email = new ResetPasswordMail($this->details);
            Mail::to($this->details['email'])->send($email);
        } catch (Exception $ex) {
            $this->failed($ex);
            report($ex);
        }
    }

    public function failed(Exception $exception) {
        report($exception->getMessage());
        $exception->getMessage();
    }

}
