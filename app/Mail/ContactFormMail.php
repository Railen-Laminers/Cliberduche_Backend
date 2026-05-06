<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('📩 New Contact: ' . $this->data['subject'])
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->to(config('mail.from.address')) // Sends to railen.laminero@gmail.com
            ->replyTo($this->data['email'], $this->data['name'])
            ->view('emails.contact');
    }
}