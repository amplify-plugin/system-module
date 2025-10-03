<?php

namespace Amplify\System\Checks\SslCertificate;

use RuntimeException;

class InvalidUrlException extends RuntimeException
{
    public static function make(): self
    {
        return new self('The given URL is invalid for the SSL certificate check');
    }
}
