<?php

namespace Amplify\System\Providers;

use Amplify\System\Exceptions\SystemException;
use Amplify\System\Mail\Transport\MicrosoftGraphTransport;
use Amplify\System\Services\MicrosoftGraphApiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MicrosoftGraphMailProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('microsoft-graph', function (array $config): MicrosoftGraphTransport {
            throw_if(
                blank($config['from']['address'] ?? []),
                new SystemException('Configuration key from.address for microsoft-graph mailer is missing.')
            );

            $accessTokenTtl = $config['access_token_ttl'] ?? 3000;
            if (! is_int($accessTokenTtl)) {
                throw new SystemException("Configuration key access_token_ttl for microsoft-graph mailer has invalid value: ".var_export($accessTokenTtl, true).".");
            }

            return new MicrosoftGraphTransport(
                new MicrosoftGraphApiService(
                    tenantId: $this->requireConfigString($config, 'tenant_id'),
                    clientId: $this->requireConfigString($config, 'client_id'),
                    clientSecret: $this->requireConfigString($config, 'client_secret'),
                    accessTokenTtl: $accessTokenTtl,
                ),
            );
        });
    }

    protected function requireConfigString(array $config, string $key): string
    {
        if (! array_key_exists($key, $config)) {
            throw new SystemException("Configuration key {$key} for microsoft-graph mailer is missing.");
        }

        $value = $config[$key];
        if (! is_string($value) || $value === '') {
            $invalidValue = var_export($value, true);
            throw new SystemException("Configuration key {$key} for microsoft-graph mailer has invalid value: {$invalidValue}.");
        }

        return $value;
    }
}
