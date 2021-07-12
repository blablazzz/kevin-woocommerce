<?php

defined( 'ABSPATH' ) || exit;

class WC_Kevin_Client {


    private $client;
    /**
     * WC_Kevin_Client constructor.
     * @param $client_id
     * @param $client_secret
     * @param string[] $options
     * @throws \Kevin\KevinException
     */
    public function __construct($client_id, $client_secret, $options = ['version' => '0.3'])
    {
        $this->client = new \Kevin\Client($client_id, $client_secret, $options);
    }

    /**
     * @param $countryCode
     * @return array|mixed
     */
    public function getBanks($countryCode) {
        try {
            $banks = $this->client->auth()->getBanks(['countryCode' => $countryCode]);
        } catch (\Kevin\KevinException $exception) {

            return [];
        }

        return $banks['data'];
    }

    /**
     * @return array|mixed
     */
    public function getPaymentMethods() {
        try {
            $banks = $this->client->auth()->getPaymentMethods();
        } catch (\Kevin\KevinException $exception) {
            return [];
        }

        return $banks['data'];
    }


    /**
     * @param $attr
     * @return array
     * @throws \Kevin\KevinException
     */
    public function initPayment($attr) {
        return $this->client->payment()->initPayment($attr);
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return array
     * @throws \Kevin\KevinException
     */
    public function getPaymentStatus($paymentId, $attr) {
        return $this->client->payment()->getPaymentStatus($paymentId, $attr);
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return array
     * @throws \Kevin\KevinException
     */
    public function initiatePaymentRefund($paymentId, $attr) {
        return $this->client->payment()->initiatePaymentRefund($paymentId, $attr);
    }
}
