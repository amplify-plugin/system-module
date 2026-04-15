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
            return new MicrosoftGraphTransport(
                tenantId: $config['tenant_id'] ?? null,
                clientId: $config['client_id'] ?? null,
                clientSecret: $config['client_secret'] ?? null,
            );
        });
    }
}
