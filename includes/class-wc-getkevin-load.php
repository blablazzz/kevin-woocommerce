<?php
defined('ABSPATH') or exit;

class Wc_GetKevin_Load
{
    protected $errors = [];
    const ADMIN_SETTINGS_PATH = 'admin.php?page=wc-settings&tab=checkout&section=getkevin';
    const KEVIN_DOCS_URL = 'https://docs.getkevin.eu/';

    /**
     * Loads GetKevin plugin
     */
    public function load()
    {
        /*
         * Load translations and check for woocommerce
         */
        add_action('plugins_loaded', [$this, 'loadGetKevinGateway']);
        /*
         * Show admin errors
         */
        add_action('admin_notices', [$this, 'displayErrors']);
        /*
         * Add PaymentMethod
         */
        add_filter('woocommerce_payment_gateways', [$this, 'addGetKevinPaymentMethod']);
        /*
         * Add
         */
        add_filter( 'plugin_action_links_' . WCGatewayGetKevinPluginPath.'/getkevin.php', array( $this, 'addGetKevinActionLinks' ) );
    }

    public function loadGetKevinGateway()
    {
        load_plugin_textdomain(
            'woocommerce-gateway-getkevin',
            false,
            WCGatewayGetKevinPluginPath. '/languages/'
        );

        if(!class_exists('woocommerce')) {
            $this->addError('GetKevin plugin needs WooCommerce');
            return false;
        }

        require_once "class-wc-getkevin-gateway.php";

        return true;
    }

    /**
     * Shows errors on the admin page if something is wrong
     */
    public function displayErrors()
    {
        $messages = implode(PHP_EOL, $this->getErrors());

        $errors =  array(
            'prefix'   => __('WooCommerce Payment Gateway - kevin.', 'woocommerce-gateway-getkevin'),
            'messages' => $messages
        );

        if (!empty($errors['messages'])) {
            echo '<div class="error"><p><b>'.$errors['prefix'].': </b><br>'.$errors['messages'].'</p></div>';
        }
    }

    /**
     * Adds GetKevin payment method
     */
    public function addGetKevinPaymentMethod($methods) {
        $methods[] = 'WC_Gateway_GetKevin';

        return $methods;
    }

    /**
     * Adds Admin settings action link
     */
    public function addGetKevinActionLinks($links) {


        $adminSettingsTranslate = __('Settings', 'woocommerce-gateway-getkevin');
        $adminSettingsPath = admin_url($this::ADMIN_SETTINGS_PATH);
        $htmlSettingsLink      = '<a href="' . $adminSettingsPath . '">' . $adminSettingsTranslate . '</a>';
        $htmlDocumentationLink = '<a href="' . $this::KEVIN_DOCS_URL . '" target="_blank">API Documentation</a>';

        array_unshift($links, $htmlSettingsLink, $htmlDocumentationLink);

        return $links;
    }


    /**
     * Adds error to be shown in admin page
     */
    public function addError($errorText, $pluginPath = 'woocommerce-gateway-getkevin')
    {
        $error = __($errorText, $pluginPath);

        $this->errors[] = $error;

        return $this;
    }

    /**
     * returns all the added errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}