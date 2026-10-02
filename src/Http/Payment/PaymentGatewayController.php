<?php

namespace Amplify\System\Http\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Random\RandomException;

class PaymentGatewayController extends Controller
{
    public function initialize(): JsonResponse
    {
        try {
            $driver = config('amplify.payment.default');

            $config = config("amplify.payment.gateways.{$driver}", []);

            unset($config['adapter']);

            $config['allow_credit_payments'] = config('amplify.payment.allow_credit_payments', false);
            $config['allow_payments'] = config('amplify.payment.allow_payments', false);

            return response()->json([
                'success' => true,
                'message' => null,
                'data' => $this->encrypt([
                    'driver' => $driver,
                    'config' => $config,
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => ''
            ], 500);
        }
    }

    private function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @throws RandomException
     * @throws \JsonException
     */
    private function encrypt(array $config): string
    {
        $secret = config('amplify.client_code', 'ACP');

        // Derive exactly 32 bytes for AES-256.
        $key = hash('sha256', $secret, true);

        // AES-GCM recommends a 12-byte nonce.
        $iv = random_bytes(12);

        $tag = '';

        $json = json_encode(
            $config,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        $ciphertext = openssl_encrypt(
            $json,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Unable to encrypt payment configuration.');
        }

        /*
         * Custom JWT-like format:
         *
         * pg.<iv>.<ciphertext>.<tag>
         */
        return implode('.', [
            'pg',
            $this->encode($iv),
            $this->encode($ciphertext),
            $this->encode($tag),
        ]);
    }
}
