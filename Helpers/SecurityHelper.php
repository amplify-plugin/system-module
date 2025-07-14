<?php

namespace Amplify\System\Helpers;

class SecurityHelper
{
    public static function passwordLength(): int
    {
        return config('amplify.security.password_length', PASSWORD_MIN_LEN);
    }
}
