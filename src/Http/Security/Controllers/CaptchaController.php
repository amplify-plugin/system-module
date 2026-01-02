<?php

namespace Amplify\System\Http\Security\Controllers;

use Amplify\System\Base\Captcha;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class CaptchaController
 */
class CaptchaController extends Controller
{
    /**
     * get CAPTCHA
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getCaptcha(Captcha $captcha, string $config = 'default')
    {
        if (ob_get_contents()) {
            ob_clean();
        }

        return $captcha->create($config);
    }

    /**
     * get CAPTCHA api
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getCaptchaApi(Captcha $captcha, string $config = 'default')
    {
        return $captcha->create($config, true);
    }

    public function reloadCaptcha(Captcha $captcha, Request $request): JsonResponse
    {
        $captcha_type = $request->input('recaptcha_type', config('amplify.basic.recaptcha_type', 'math'));

        return response()->json(['captcha' => $captcha->img($captcha_type)]);
    }
}
