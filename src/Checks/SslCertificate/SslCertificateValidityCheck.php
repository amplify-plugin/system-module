<?php

namespace Amplify\System\Checks\SslCertificate;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\SslCertificate\SslCertificate;
class SslCertificateValidityCheck extends Check
{
    public ?string $url = null;

    /**
     * @param string $url
     *
     * @return $this
     */
    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Result
     */
    public function run(): Result
    {
        if ($this->url === null) {
            throw InvalidUrlException::make();
        }

        $certificate = SslCertificate::createForHostName($this->url);
        $valid = $certificate->isValid();

        $result = Result::make()
            ->meta([
                'valid' => $valid,
                'issuer' => $certificate->getIssuer(),
                'domain' => $certificate->getDomain(),
                'algo' => $certificate->getSignatureAlgorithm(),
                'organization' => $certificate->getOrganization(),
                'aliases' => $certificate->getAdditionalDomains(),
                'validated_at' => $certificate->validFromDate(),
                'expired_at' => $certificate->expirationDate(),

            ])
            ->shortSummary('SSL Certification valid');

        if (! $valid) {
            return $result->failed('SSL Certification is not valid');
        }

        return $result->ok();
    }
}
