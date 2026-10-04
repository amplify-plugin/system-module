<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\ContactLogin;
use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class ResolveSubmitter
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $channel = $context->payload['checkout']['channel'];

        if ($channel == 'web') {
            $id = session('contact_login_id');
            $contactLogin = ContactLogin::find($id);

            $submitter = $contactLogin->impersonate != null
                ? $contactLogin->impersonate
                : customer(true);
        } else {
            $submitter = match ($channel) {
                'admin' => backpack_user(),
                'api' => auth('api')->user(),
                default => null,
            };
        }

        $context->resolved['submitter'] = $submitter;

        return $next($context);
    }
}
