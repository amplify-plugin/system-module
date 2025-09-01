<?php

namespace Amplify\System\Backend\Services;

class PunchOutApiService
{
    /**
     * Any Class that's enable the Payment API interface
     *
     * @var mixed
     */
    protected $serviceInstance;

    /**
     * PaymentApiService Constructor
     */
    public function __construct()
    {
        $config = config('amplify.punchout.configurations.'.config('amplify.punchout.default'));

        $this->serviceInstance = new $config['adapter'];
    }

    /**
     * This function returns the current adapter
     *  being used to make the api calls
     */
    public function service()
    {
        return $this->serviceInstance;
    }

    /**
     * This function returns the current adapter
     *  being used to make the api calls
     */
    public function adapter()
    {
        return $this->serviceInstance->adapter;
    }

    /**
     * This function will check if the Payment can be enabled
     */
    public function enabled(): bool
    {
        return $this->serviceInstance->enabled();
    }

    public function getVerifyingPost()
    {
        return $this->serviceInstance->getVerifyingPost();
    }

    public function getCards()
    {
        return $this->serviceInstance->getCards();
    }

    public function payPayment()
    {
        return $this->serviceInstance->getCards();
    }
}
