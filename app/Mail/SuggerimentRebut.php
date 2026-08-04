<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Suggeriment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuggerimentRebut extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Suggeriment $suggeriment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[FemCastells] Nou suggeriment de ' . $this->suggeriment->casteller->alias,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.suggeriment-rebut');
    }
}
