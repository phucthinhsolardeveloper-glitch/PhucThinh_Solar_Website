<?php

namespace App\Mail;

use App\Domain\Commerce\OrderSheet;
use App\Support\LogSafe;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;
use Throwable;

/** New order email, to the shop (with reply-to the customer) or to the customer as a receipt. */
class OrderNotification extends Mailable
{
    use Queueable;

    /**
     * Order sheet to attach, as stored when the order was placed; empty = no attachment. A declared default, so a
     * message queued before this property existed still rebuilds.
     *
     * @var array<string, mixed>
     */
    public array $sheet = [];

    private ?string $sheetBytes = null;

    /**
     * @param  array{subject: string, heading: string, intro: string, closing: string}  $copy
     * @param  array<string, string>  $fields  customer and delivery details
     * @param  list<array{name: string, option: string, quantity: int, price: float, total: float}>  $items
     * @param  array{subtotal: float, shipping: float, total: float}  $totals
     */
    public function __construct(public array $copy, public array $fields, public array $items, public array $totals, private readonly string $replyEmail = '', private readonly string $replyName = '', public array $actions = []) {}

    public function envelope(): Envelope
    {
        $replyTo = filter_var($this->replyEmail, FILTER_VALIDATE_EMAIL) ? [new Address($this->replyEmail, $this->replyName)] : [];

        return new Envelope(subject: $this->copy['subject'], replyTo: $replyTo);
    }

    /** @param array<string, mixed> $sheet */
    public function withSheet(array $sheet): static
    {
        $this->sheet = $sheet;
        $this->sheetBytes = null;

        return $this;
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order', text: 'mail.order-text', with: ['sheetAttached' => $this->sheetPdf() !== null]);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $pdf = $this->sheetPdf();

        return $pdf === null ? [] : [Attachment::fromData(fn () => $pdf, OrderSheet::filename($this->sheet))->withMime('application/pdf')];
    }

    /** The confirmation goes out even when the sheet cannot be rendered; the failure is logged. */
    private function sheetPdf(): ?string
    {
        if ($this->sheet === []) {
            return null;
        }
        try {
            return $this->sheetBytes ??= app(OrderSheet::class)->pdf($this->sheet);
        } catch (Throwable $e) {
            Log::error('Order sheet could not be rendered', ['error' => LogSafe::error($e)]);

            return null;
        }
    }

    public static function money(float $value): string
    {
        return number_format($value, 0, ',', '.').'đ';
    }
}
