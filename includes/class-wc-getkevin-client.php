<?php

defined( 'ABSPATH' ) || exit;

class WC_Kevin_Client {


    private $client;
    private $webhookUrl;
    private $endpointSecret;

    /**
     * WC_Kevin_Client constructor.
     * @param $client_id
     * @param $client_secret
     * @param string[] $options
     * @throws \Kevin\KevinException
     */
    public function __construct($client_id, $client_secret, $options = ['version' => '0.3'], $webhookUrl, $endpointSecret)
    {
        $this->client = new \Kevin\Client($client_id, $client_secret, $options);
        $this->webhookUrl = $webhookUrl;
        $this->endpointSecret = $endpointSecret;
    }

    /**
     * @param $countryCode
     * @return array|mixed
     */
    public function getBanks($countryCode = null) {
        try {
            $options = [];
            if($countryCode)
            {
                $options = ['countryCode' => $countryCode];
            }
            $banks = $this->client->auth()->getBanks($options);
        } catch (\Kevin\KevinException $exception) {

            return [];
        }

        return $banks['data'];
    }

    /**
     * @deprecated
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
     * @return array|mixed
     */
    public function getProjectSettings() {
        try {
            $projectSettings = $this->client->auth()->getProjectSettings();
        } catch (\Kevin\KevinException $exception) {
            return [];
        }

        return $projectSettings;
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

    /**
     * @param $endpointSecret
     * @param $requestBody
     * @param $headers
     * @param $webhookUrl
     * @return bool
     */
    public function verifySignature($requestBody, $headers)
    {
        return \Kevin\SecurityManager::verifySignature($this->endpointSecret, $requestBody, $headers, $this->webhookUrl, 300000);
    }
}
