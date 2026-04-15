<?php

namespace Amplify\System\Mail\Transport;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected const TOKEN_TTL = 3000;

    public function __construct(
        protected readonly string $tenantId,
        protected readonly string $clientId,
        protected readonly string $clientSecret,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'microsoft+graph+api://';
    }

    /**
     * @throws RequestException
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $html = $email->getHtmlBody();
        [$attachments, $html] = $this->prepareAttachments($email, $html);

        $payload = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => $html === null ? 'Text' : 'HTML',
                    'content' => $html ?: $email->getTextBody(),
                ],
                'toRecipients' => $this->transformEmailAddresses($this->getRecipients($email, $envelope)),
                'ccRecipients' => $this->transformEmailAddresses(collect($email->getCc())),
                'bccRecipients' => $this->transformEmailAddresses(collect($email->getBcc())),
                'replyTo' => $this->transformEmailAddresses(collect($email->getReplyTo())),
                'sender' => $this->transformEmailAddress($envelope->getSender()),
                'attachments' => $attachments,
            ],
            'saveToSentItems' => false,
        ];

        if (filled($headers = $this->getInternetMessageHeaders($email))) {
            $payload['message']['internetMessageHeaders'] = $headers;
        }

        $from = $envelope->getSender()->getAddress();

        Http::withToken($this->getAccessToken())
            ->baseUrl('https://graph.microsoft.com/v1.0')
            ->post("/users/{$from}/sendMail", $payload)
            ->throw();
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('microsoft-graph-api-access-token-'.$this->tenantId, self::TOKEN_TTL, function (): string {
            $response = Http::asForm()
                ->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                ]);

            $response->throw();

            $accessToken = $response->json('access_token');
            throw_unless(is_string($accessToken), new \Error('Microsoft Graph token response missing access_token.'));

            return $accessToken;
        });
    }

    protected function prepareAttachments(Email $email, ?string $html): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $fileName = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $contentId = $headers->has('Content-ID') ? $headers->get('Content-ID')?->getBody()[0] ?? null : null;
            $contentId = filled($contentId) ? $contentId : $fileName;

            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $contentId,
                'contentType' => implode('/', [$attachment->getMediaType(), $attachment->getMediaSubtype()]),
                'contentBytes' => base64_encode($attachment->getBody()),
                'contentId' => $contentId,
                'isInline' => $headers->getHeaderBody('Content-Disposition') === 'inline',
            ];
        }

        return [$attachments, $html];
    }

    protected function transformEmailAddresses(Collection $recipients): array
    {
        return $recipients
            ->map(fn (Address $recipient) => $this->transformEmailAddress($recipient))
            ->toArray();
    }

    protected function transformEmailAddress(Address $address): array
    {
        return [
            'emailAddress' => [
                'address' => $address->getAddress(),
            ],
        ];
    }

    protected function getRecipients(Email $email, Envelope $envelope): Collection
    {
        return collect($envelope->getRecipients())
            ->filter(fn (Address $address) => ! in_array($address, array_merge($email->getCc(), $email->getBcc()), true));
    }

    protected function getInternetMessageHeaders(Email $email): ?array
    {
        return collect($email->getHeaders()->all())
            ->filter(fn (HeaderInterface $header) => str_starts_with($header->getName(), 'X-'))
            ->map(fn (HeaderInterface $header) => ['name' => $header->getName(), 'value' => $header->getBodyAsString()])
            ->values()
            ->all() ?: null;
    }
}
