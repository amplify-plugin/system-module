<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Facades\ErpApi;

class ErpIsEnabled
{
    public function handle($data, $next)
    {
        if (!ErpApi::enabled()) {
            abort(response()->json([
                'success' => false,
                'message' => 'ERP Service is not enabled.'], 401));
        }

        return $next($data);
    }

}
