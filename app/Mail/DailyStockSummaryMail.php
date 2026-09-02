<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyStockSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(public array $summary) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily stock summary · '.$this->summary['date'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-stock-summary',
        );
    }
}
