<?php

namespace Amplify\System\Payment;

class PayApiService
{
    /**
     * Any Class that's enable the Payment API interface
     *
     * @var mixed
     */
    protected $adapter;

    /**
     * PaymentApiService Constructor
     */
    public function __construct()
    {
        $config = config('amplify.payment.gateways.'.config('amplify.payment.default'));

        $this->adapter = new $config['adapter'];
    }

    /**
     * This function returns the current adapter
     *  being used to make the api calls
     */
    public function adapter()
    {
        return $this->adapter;
    }

    /**
     * This function will check if the Payment can be enabled
     */
    public function enabled(): bool
    {
        return $this->adapter->enabled();
    }

    public function getVerifyingPost()
    {
        return $this->adapter->getVerifyingPost();
    }

    public function getCards()
    {
        return $this->adapter->getCards();
    }

    public function payPayment()
    {
        return $this->adapter->getCards();
    }
}
