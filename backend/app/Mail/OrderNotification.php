<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** New order email, to the shop (with reply-to the customer) or to the customer as a receipt. */
class OrderNotification extends Mailable
{
    use Queueable;

    /**
     * @param  array{subject: string, heading: string, intro: string, closing: string}  $copy
     * @param  array<string, string>  $fields  customer and delivery details
     * @param  list<array{name: string, option: string, quantity: int, price: float, total: float}>  $items
     * @param  array{subtotal: float, shipping: float, total: float}  $totals
     */
    public function __construct(public array $copy, public array $fields, public array $items, public array $totals, private readonly string $replyEmail = '', private readonly string $replyName = '') {}

    public function envelope(): Envelope
    {
        $replyTo = filter_var($this->replyEmail, FILTER_VALIDATE_EMAIL) ? [new Address($this->replyEmail, $this->replyName)] : [];

        return new Envelope(subject: $this->copy['subject'], replyTo: $replyTo);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order', text: 'mail.order-text');
    }

    public static function money(float $value): string
    {
        return number_format($value, 0, ',', '.').'đ';
    }
}
