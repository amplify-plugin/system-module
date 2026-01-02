<?php

namespace Amplify\System\Payment\CenPos;

use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * PaymentApiService Constructor
     */
    private $paymentConf = [
        'payment_url' => null,
        'header' => [
            'consumerkey' => null,
            'password' => null,
        ],
    ];

    public function __construct()
    {
        $erp_code = config('amplify.erp.default');
        $erp = config('amplify.erp.configurations.'.$erp_code);

        $this->paymentConf['payment_url'] = $erp['url'];
        $this->paymentConf['header']['consumerkey'] = $erp['username'];
        $this->paymentConf['header']['password'] = $erp['password'];
    }

    public function addCard($params)
    {
        $payload = [
            'content' => [
                'CustomerNumber' => $params['customer_number'],
                'NameOnCard' => $params['name'],

                'EncryptedCreditCardNumber' => $this->createEncrypt($params['number']),
                'EncryptedCardExpDate' => $this->createEncrypt($params['expiry']),
                'EncryptedCardVerification' => $this->createEncrypt($params['cvc']),

                'CustomerEmail' => $params['customer_email'],
                'CustomerBillAddress' => $params['billing_address'],
                'CustomerZipCode' => $params['zip_code'],
            ],
        ];

        $response = Http::withHeaders($this->paymentConf['header'])
            ->withBody(json_encode($payload))
            ->post($this->paymentConf['payment_url'].'/create_token.php')
            ->collect()
            ->shift();

        return array_shift($response);
    }

    public function getCards($customerNum, $email)
    {
        $payload = [
            'content' => [
                'CustomerNumber' => $customerNum,
                'EmailAddress' => $email,
            ],
        ];

        $response = Http::withHeaders($this->paymentConf['header'])
            ->withBody(json_encode($payload))
            ->post($this->paymentConf['payment_url'].'/get_token.php')
            ->collect()
            ->shift();

        return $response;
    }

    public function createEncrypt($text)
    {
        $cipher = 'aes-128-cbc';

        if (in_array($cipher, openssl_get_cipher_methods())) {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = '8B8A9E79AB16EF2A';
            $key = '493A4DC5078A43BE';

            return openssl_encrypt($text, $cipher, $key, $options = 0, $iv);
        }

        throw new \Error('Invalid cipher');
    }
}
