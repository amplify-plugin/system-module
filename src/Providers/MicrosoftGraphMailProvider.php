<?php

namespace Amplify\System\Providers;

use Amplify\System\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MicrosoftGraphMailProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('microsoft-graph', function (array $config): MicrosoftGraphTransport {
            foreach (['tenant_id', 'client_id', 'client_secret'] as $key) {
                if (empty($config[$key]) || ! is_string($config[$key])) {
                    throw new \Error("Microsoft Graph mail config '{$key}' is missing or invalid.");
                }
            }

            throw_if(
                blank($config['from']['address'] ?? null),
                new \Error("Microsoft Graph mail config 'from.address' is missing.")
            );

            return new MicrosoftGraphTransport(
                tenantId: $config['tenant_id'],
                clientId: $config['client_id'],
                clientSecret: $config['client_secret'],
            );
        });
    }
}
