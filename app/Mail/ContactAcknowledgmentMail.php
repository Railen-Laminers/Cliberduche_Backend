<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContactAcknowledgmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('We received your message – ' . config('app.name'))
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->to($this->data['email'], $this->data['name'])
            ->view('emails.contact-acknowledgment');
    }
}