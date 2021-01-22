<?php

defined( 'ABSPATH' ) || exit;

class WC_Kevin_Client {

    private static $client_id;
    private static $client_secret;
    private static $options;

    /**
     * @param $client_id
     * @param $client_secret
     */
    public static function set_credentials( $client_id, $client_secret ) {
        self::$client_id     = $client_id;
        self::$client_secret = $client_secret;
    }

    /**
     * @param $options
     */
    public static function set_options( $options ) {
        self::$options = $options;
    }

    /**
     * @param $country_code
     *
     * @return array|mixed
     */
    public static function get_banks( $country_code ) {
        try {
            $kevinAuth = self::get_client()->auth();
            $banks     = $kevinAuth->getBanks( [ 'countryCode' => $country_code ] );
        } catch ( \Kevin\KevinException $exception ) {

            return [];
        }

        return $banks['data'];
    }

    /**
     * @param $attr
     *
     * @throws \Kevin\KevinException
     */
    public static function init_payment( $attr ) {
        $kevinPayment = self::get_client()->payment();

        return $kevinPayment->initPayment( $attr );
    }

    /**
     * @param $payment_id
     * @param $attr
     *
     * @return array
     * @throws \Kevin\KevinException
     */
    public static function get_payment_status( $payment_id, $attr ) {
        $kevinPayment = self::get_client()->payment();

        return $kevinPayment->getPaymentStatus( $payment_id, $attr );
    }

    /**
     * @return \Kevin\Client
     * @throws \Kevin\KevinException
     */
    private static function get_client() {

        return new \Kevin\Client( self::$client_id, self::$client_secret, self::$options );
    }
}
