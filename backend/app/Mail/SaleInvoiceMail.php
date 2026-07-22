<?php

namespace App\Mail;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Sale $sale) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Invoice for Sale #{$this->sale->id}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('invoices.invoice', ['sale' => $this->sale]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->sale->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
