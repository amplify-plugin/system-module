<?php

namespace Amplify\System\Checks\SslCertificate;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\SslCertificate\SslCertificate;
class SslCertificateExpiredCheck extends Check
{
    public ?string $url = null;
    protected int $warningThreshold = 20;
    protected int $errorThreshold = 14;

    /**
     * @param int $day
     *
     * @return $this
     */
    public function warnWhenSslCertificationExpiringDay(int $day): self
    {
        $this->warningThreshold = $day;

        return $this;
    }

    /**
     * @param int $day
     *
     * @return $this
     */
    public function failWhenSslCertificationExpiringDay(int $day): self
    {
        $this->errorThreshold = $day;

        return $this;
    }

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

        $certificate = SslCertificate::createForHostName($this->url, 30, false);
        $daysUntilExpired = $certificate->daysUntilExpirationDate();

        $result = Result::make()
            ->meta(['days_until_expired' => $daysUntilExpired])
            ->shortSummary($daysUntilExpired . ' days until');

        if ($certificate->isExpired()) {
            return $result->failed('The certificate has expired');
        }

        if ($daysUntilExpired < $this->errorThreshold) {
            return $result->failed("SSL certificate for {$this->url} will expires in ({$daysUntilExpired} days)");
        }

        if ($daysUntilExpired < $this->warningThreshold) {
            return $result->warning("SSL certificate for {$this->url} will expires in ({$daysUntilExpired} days)");
        }

        return $result->ok();
    }
}
