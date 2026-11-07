<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Internal notice of a new lead or order: wording from MailTemplates, then a label/value table. */
class AdminNotification extends Mailable
{
    use Queueable;

    /**
     * @param  array{subject: string, heading: string, intro: string, closing: string}  $copy
     * @param  array<string, string>  $fields
     */
    public function __construct(public array $copy, public array $fields, private readonly string $replyEmail = '', private readonly string $replyName = '') {}

    public function envelope(): Envelope
    {
        $replyTo = filter_var($this->replyEmail, FILTER_VALIDATE_EMAIL) ? [new Address($this->replyEmail, $this->replyName)] : [];

        return new Envelope(subject: $this->copy['subject'], replyTo: $replyTo);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.admin-notification', text: 'mail.admin-notification-text');
    }
}
