<?php

namespace Amplify\System\Payment\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class CenPosPaymentGateway
{
    private $paymentConfiguration;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // if (! customer_check()) {
        //     throw new Exception('CenPosPaymentGateway Service not available for unauthenticated customer.');
        // }

        $this->paymentConfiguration = config('amplify.payment.gateways.cenpos');
    }

    /**
     * @throws Exception
     */
    private function isAllowPayment()
    {
        if (! $this->paymentConfiguration || ! config('amplify.payment.allow_payments')) {
            throw new Exception('Payment Permission denied.');
        }
    }

    /**
     * @throws Exception
     */
    private function isAllowCreditPayment()
    {
        if (! $this->paymentConfiguration || ! config('amplify.payment.allow_credit_payments')) {
            throw new Exception('Credit Card Payment Permission denied.');
        }
    }

    /**
     * Getting Verifying Post Token.
     */
    public function getVerifyingPost(string $email = 's', ?float $amount = 0.00, ?string $token = null, ?string $invoiceNumber = null, ?string $type = null, ?string $address = null, ?string $zipcode = null, string $method = 'credit_card')
    {
        $this->isAllowCreditPayment();

        $endpoint = match (strtolower($method)) {
            'ach' => $this->paymentConfiguration['ach_payment_url'],
            default => $this->paymentConfiguration['payment_url'],
        };

        $payload = [
            'merchant' => $this->paymentConfiguration['cenpos_encrypted_mid'],
            'secretKey' => $this->paymentConfiguration['secret_key'],
            'email' => $email === 's' ? \ErpApi::getCustomerDetail()->CustomerEmail : $email,
            'customerCode' => \ErpApi::getCustomerDetail()->CustomerNumber,
            'amount' => $amount,
            'tokenid' => $token,
            'invoicenumber' => $invoiceNumber,
            'type' => $type,
            'ip' => request()->ip(),
            'address' => $address,
            'zipcode' => $zipcode,
        ];

        $response = Http::asForm()
            ->withoutVerifying()
            ->post($endpoint.'?app=genericcontroller&action=siteVerify', $payload)
            ->object();

        return $response->Result == 0 ? $response->Data : null;
    }

    /**
     * Getting all cards by email address.
     */
    public function getCards(string $email = 's')
    {
        $this->isAllowCreditPayment();

        $payload = [
            'verifyingpost' => $this->getVerifyingPost($email),
        ];

        $response = Http::asForm()
            ->withoutVerifying()
            ->post($this->paymentConfiguration['payment_url'].'api/GetToken/', $payload)
            ->object();

        return $response->Result == 0 ? $response->Tokens : null;
    }

    /**
     * Getting all cards by email address.
     */
    public function payPayment(string $email, float $amount, string $token, string $invoiceNumber, string $type = 'Sale')
    {
        $this->isAllowCreditPayment();

        $payload = [
            'verifyingpost' => $this->getVerifyingPost($email, $amount, $token, $invoiceNumber, $type),
            'tokenid' => $token,
        ];

        $response = Http::asForm()
            ->withoutVerifying()
            ->post($this->paymentConfiguration['payment_url'].'api/UseToken/', $payload)
            ->object();

        return $response;
    }
}
