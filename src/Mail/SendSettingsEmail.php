<?php

namespace Amplify\System\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendSettingsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $width = '570';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        if (! empty($data['body_width'])) {
            $this->width = $data['body_width'];
        }

        if (isset($data['body_width'])) {
            unset($data['body_width']);
        }

        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name', config('app.name'))),
            subject: $this->data['subject'] ?? 'Email Subject',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if (isset($this->data['attachments'])) {
            foreach ($this->data['attachments'] as $attachment) {
                $attachments[] = Attachment::fromPath($attachment);
            }
        }

        return $attachments;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('system::email.settings_email', [
            'email_content' => $this->data['email_content'] ?? '',
            'show_button' => $this->data['show_button'] ?? '',
            'button_url' => $this->data['button_url'] ?? '',
            'button_text' => $this->data['button_text'] ?? '',
            'is_customer_mail' => $this->data['is_customer_mail'] ?? '',
            'width' => $this->width,
        ]);
    }
}
