<?php

namespace Amplify\System\Payment\Services;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Http;

class CentPosPayService
{
    /**
     * @var array|Repository|Application|mixed
     */
    private array $config;

    public function __construct()
    {
        $this->config = config('amplify.payment.gateways.cenpos');
    }

    /**
     * This function will check if the Payment can be enabled
     * TODO: check if payment service is enable
     */
    public function enabled(): bool
    {
        // return $this->config['enabled'] ?? false;
        return true;
    }

    /**
     * @throws Exception
     */
    private function isAllowCreditPayments()
    {
        if (! config('amplify.payment.allow_credit_payments')) {
            throw new Exception('Credit Card Payment Permission denied.');
        }
    }

    /**
     * @throws Exception
     */
    private function isAllowPayment()
    {
        if (! config('amplify.payment.allow_payments')) {
            throw new Exception('Payment Permission denied.');
        }
    }

    private function post(string $url, $payload = null): object
    {
        return Http::asForm()
            ->post(($this->config['url'].$url), $payload)
            ->object();
    }

    /**
     * @throws Exception
     */
    public function getVerifyingPost(
        string $email = 's',
        ?float $amount = 0.00,
        ?string $token = null,
        ?string $invoiceNumber = null,
        ?string $type = null
    ) {
        $this->isAllowPayment();

        $payload = [
            'merchant' => $this->config['cenpos_encrypted_mid'],
            'secretKey' => $this->config['secret_key'],
            'email' => $email === 's' ? \ErpApi::getCustomerDetail()->CustomerEmail : $email,
            'amount' => $amount,
            'tokenid' => $token,
            'invoicenumber' => $invoiceNumber,
            'type' => $type,
            'ip' => request()->ip(),
        ];

        $response = $this->post('?app=genericcontroller&action=siteVerify', $payload);

        return $response->Result == 0 ? $response->Data : null;
    }

    /**
     * Getting all cards by email address.
     *
     * @throws Exception
     */
    public function getCards(string $email = 's')
    {
        $this->isAllowPayment();

        $payload = [
            'verifyingpost' => $this->getVerifyingPost($email),
        ];

        $response = $this->post('api/GetToken/', $payload);

        return $response->Result == 0 ? $response->Tokens : null;
    }

    /**
     * Getting all cards by email address.
     *
     * @throws Exception
     */
    public function payPayment(string $email, float $amount, string $token, string $invoiceNumber, string $type = 'Sale'): object
    {
        $this->isAllowPayment();

        $payload = [
            'verifyingpost' => $this->getVerifyingPost($email, $amount, $token, $invoiceNumber, $type),
            'tokenid' => $token,
        ];

        $response = $this->post('api/UseToken/', $payload);

        return $response;
    }
}
