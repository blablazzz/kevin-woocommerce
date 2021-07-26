<?php

defined('ABSPATH') || exit;

/**
 * Class WC_Gateway_GetKevin
 */
class WC_Gateway_GetKevin extends WC_Payment_Gateway
{

    const KEVIN_LOGO = 'assets/images/kevin.png';
    const GETKEVIN_ACTION_JS = 'assets/js/backend/action.js';
    protected $client_id;
    protected $client_secret;
    protected $creditor_name;
    protected $creditor_account;
    protected $redirect_preferred;

    protected $pluginSettings;
    protected $clientOptions;

    protected $paymentStatusStarted;
    protected $paymentStatusPending;
    protected $paymentStatusCompleted;
    protected $paymentStatusFailed = 'failed';

    protected $uiLocales = array('en', 'lt', 'lv', 'ee', 'fi', 'se', 'ru');
    protected $uiLocaleDefault = 'en';

    public function __construct()
    {

        if (!class_exists('WC_Kevin_Client'))
        {
            require_once 'class-wc-getkevin-client.php';
        }

        $this->id = 'getkevin';
        $this->has_fields = true;
        $this->method_title = 'kevin.';
        $this->method_description = __('kevin. is a payment infrastructure company which offers payment initiation service in EU&EEA.', "woocommerce-gateway-getkevin");
        $this->icon = apply_filters('woocommerce_getkevin_icon', WCGatewayGetKevinPluginUrl . $this::KEVIN_LOGO);

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->client_id = $this->get_option('client_id');
        $this->client_secret = $this->get_option('client_secret');
        $this->creditor_name = $this->get_option('creditor_name');
        $this->creditor_account = $this->get_option('creditor_account');
        $this->redirect_preferred = $this->get_option('redirect_preferred') === 'yes';
        $this->paymentStatusCompleted = $this->get_option('paymentCompletedOrderStatus');
        $this->paymentStatusPending = $this->get_option('paymentPendingOrderStatus');
        $this->paymentStatusStarted = $this->get_option('paymentNewOrderStatus');

        $this->clientOptions = [
            'version' => '0.3',
            'pluginVersion' => "2.1.3",
            'pluginPlatform' => "Wordpress/WooCommerce",
            'pluginPlatformVersion' => $GLOBALS['wp_version'] . "/" . WC_VERSION,
        ];


        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'handle_webhook'));


        //Processes and saves options.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text'), 10, 2);

        add_filter('woocommerce_order_fully_refunded_status', array($this, 'filter_woocommerce_order_fully_refunded_status'), 10, 3);

        $this->supports = ['products'];
    }

    public function init_form_fields()
    {

        if (!class_exists('Wc_GetKevin_Settings'))
        {
            require_once 'class-wc-getkevin-settings.php';
        }
        $this->pluginSettings = (new Wc_GetKevin_Settings());
        $this->form_fields = $this->pluginSettings->getFormFields();
    }

    public function admin_options()
    {
        $this->form_fields = $this->pluginSettings->updateAdminSettings($this->form_fields);
        $all_fields = $this->get_form_fields();
        $tabs = $this->generateTabs(
            [
                [
                    'name' => __('Main Settings', 'woocommerce-gateway-getkevin'),
                    'slice' => array_slice($all_fields, 0, 8)
                ],
                [
                    'name' => __('Order statuses', 'woocommerce-gateway-getkevin'),
                    'slice' => array_slice($all_fields, 8, 12)
                ],
            ]
        );
        $this->pluginSettings->buildAdminFormHtml($tabs);

        wp_enqueue_script(
            'custom-backend-script',
            WCGatewayGetKevinPluginUrl . self::GETKEVIN_ACTION_JS,
            array('jquery')
        );
    }

    public function validate_client_id_field($key, $value)
    {
        if (!preg_match("/^[a-zA-Z0-9.-]{10,100}$/", $value))
        {
            WC_Admin_Settings::add_error(esc_html__('Client ID must be made from letters, digits and special character (-). (Ex: 111111bb-ccc2-45df-9afC-e111dd000000).', 'woocommerce-gateway-getkevin'));
            return "";
        }
        return $value;
    }

    public function validate_client_secret_field($key, $value)
    {
        if (!preg_match("/^[a-zA-Z0-9]{50,250}$/", $value))
        {
            WC_Admin_Settings::add_error(esc_html__('Client secret must be a string from letters and digits.', 'woocommerce-gateway-getkevin'));
            return "";
        }
        return $value;
    }

    public function validate_creditor_name_field($key, $value)
    {
        if (!preg_match("/^[a-zA-Z0-9. ]{3,250}$/", $value))
        {
            WC_Admin_Settings::add_error(esc_html__('Creditor name must be a string without any special characters.', 'woocommerce-gateway-getkevin'));
            return "";
        }
        return $value;
    }

    public function validate_creditor_account_field($key, $value)
    {
        if (!preg_match("/^([A-Z]{2})(\S{8,36})/", $value))
        {
            WC_Admin_Settings::add_error(esc_html__('Creditor account field must valid IBAN (Ex: LT01234567891011121314).', 'woocommerce-gateway-getkevin'));
            return "";
        }
        return $value;
    }

    public function payment_fields()
    {

        $description = $this->get_description();
        $country_code = WC()->customer->get_billing_country();
        $client = new WC_Kevin_Client($this->client_id, $this->client_secret, $this->clientOptions);

        $paymentMethods = $client->getPaymentMethods();

        $banks = $client->getBanks($country_code);

        ob_start();

        if ($banks)
        {
            echo '<div class="wc-kevin-banks-list">';

            if (in_array("bank", $paymentMethods))
            {
                foreach ($banks as $bank)
                {
                    $html = '<div class="wc-kevin-bank">';
                    $html .= '<p>';
                    $html .= '<label>';
                    $html .= '<input type="radio" name="payment[bank_id]" value="' . esc_attr($bank['id']) . '" title="' . esc_attr($bank['name']) . '">';
                    $html .= '<img src="' . $bank['imageUri'] . '" height="48" alt="' . esc_attr($bank['name']) . '">';
                    $html .= esc_html($bank['name']);
                    $html .= '</label>';
                    $html .= '</p>';
                    $html .= '</div>';
                    echo $html;
                }
            }
            if (in_array("card", $paymentMethods))
            {

                $html = '<div class="wc-kevin-bank">';
                $html .= '<p>';
                $html .= '<label>';
                $html .= '<input type="radio" name="payment[bank_id]" value="card" title="card">';
                $html .= '<img src="' . WCGatewayGetKevinPluginUrl . "/assets/images/credit_card_stock.png" . '" height="48">';
                $html .= 'Credit/Debit card';
                $html .= '</label>';
                $html .= '</p>';
                $html .= '</div>';
                echo $html;
            }
            echo '</div>';
        }

        if ($description)
        {
            echo '<div class="wc-kevin-banks-description">';
            echo apply_filters('wc_kevin_description', wpautop(wp_kses_post($description)), $this->id);
            echo '</div>';
        }

        ob_end_flush();

        wp_register_style('getkevin_styles', plugins_url('assets/css/kevin-styles.css', WC_KEVIN_MAIN_FILE), array(), WC_KEVIN_VERSION);
        wp_enqueue_style('getkevin_styles');
    }

    public function validate_fields()
    {
        if (empty($_REQUEST['payment']['bank_id']))
        {
            wc_add_notice(__('Please select your bank.', 'woocommerce-gateway-getkevin'), 'error');

            return false;
        }

        return true;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Payment: Started
        $order->add_order_note(__('kevin. Payment started.', 'woocommerce-gateway-getkevin'));

        try
        {
            $redirect_url = $this->get_return_url($order);
            $webhook_url = WC()->api_request_url('WC_Gateway_GetKevin');

            $attr = [
                'amount' => number_format($order->get_total(), 2, '.', ''),
                'currencyCode' => $order->get_currency(),
                'description' => (string)$order->get_id(),
                'identifier' => ['email' => $order->get_billing_email()],
                'redirectPreferred' => $this->redirect_preferred,
                'Redirect-URL' => $redirect_url,
                'Webhook-URL' => $webhook_url,
            ];

            $attr['bankPaymentMethod'] = [
                'creditorName' => $this->creditor_name,
                'endToEndId' => strval($order->get_id()),
                'creditorAccount' => [
                    'iban' => $this->creditor_account,
                ],
            ];

            if (!empty($_REQUEST['payment']['bank_id']))
            {
                if ($_REQUEST['payment']['bank_id'] == 'card')
                {
                    $attr['cardPaymentMethod'] = [
                    ];
                    $attr['paymentMethodPreferred'] = 'card';
                } else
                {
                    $attr['bankId'] = $_REQUEST['payment']['bank_id'];
                }
            }

            $client = new WC_Kevin_Client($this->client_id, $this->client_secret, $this->clientOptions);
            $response = $client->initPayment($attr);

            $this->resolveGetKevinStatusGroup($order, $response['statusGroup']);
            $this->updateOrderMetaDataGetKevin($order, $response);
            $response['confirmLink'] = add_query_arg(array('lang' => $this->get_ui_locale()), $response['confirmLink']);
            wc_maybe_reduce_stock_levels($order_id);
            return array(
                'result' => 'success',
                'redirect' => $response['confirmLink'],
            );
        } catch (\Kevin\KevinException $e)
        {
            $order->add_order_note($e->getMessage());

            return array(
                'result' => 'fail',
                'redirect' => '',
            );
        }
    }

    public function handle_webhook()
    {
        $inputData = file_get_contents('php://input');
        $inputData = json_decode($inputData, true);

        if (isset($inputData['id']) && $inputData['id'] && isset($inputData['statusGroup']) && $inputData['statusGroup'])
        {
            $attr = [
                'meta_key' => '_kevin_id',
                'meta_value' => $inputData['id'],
                'meta_compare' => '='
            ];

            $orders = wc_get_orders($attr);
            if ($orders && !empty($orders) && $orders[0])
            {
                $order = $orders[0];

                $paymentId = $inputData['id'];
                $client = new WC_Kevin_Client($this->client_id, $this->client_secret, $this->clientOptions);

                $response = $client->getPaymentStatus($paymentId, []);

                $inputData = $this->resolvePaymentStatus($response, $inputData);

                if ($order->get_meta('_kevin_status_group') == $inputData['statusGroup'] && $order->get_meta('_kevin_status') == $inputData['status'])
                {
                    $order->add_order_note(sprintf(__('kevin. Duplicate webhook: Payment status is %s', 'woocommerce-gateway-getkevin'), $inputData['statusGroup']));
                    status_header(200);
                    exit();
                }

                $this->resolveGetKevinStatusGroup($order, $inputData['statusGroup']);
                $order->update_meta_data('_kevin_status', $inputData['status']);
                $order->update_meta_data('_kevin_status_group', $inputData['statusGroup']);

                //Let getKevin know that everything is okey
                status_header(200);
                exit();
            }
        }
        //Let getKevin know that something went wrong so they retry later
        status_header(400);
        exit();
    }

    /**
     * docs: https://docs.getkevin.eu/public/platform/v0.3#operation/getPaymentStatus
     * Function meant to check webhook call with real current kevin data from getKevin api
     * Returns status group for main logic resolver resolveGetKevinStatusGroup()
     * @param $response
     * @param $inputData
     */
    public function resolvePaymentStatus($response, $inputData)
    {
        $inputData['statusGroup'] = $response['group'];

        if(isset($response['bankStatus']))
        {
            $inputData['status'] = $response['bankStatus'];
        }
        elseif (isset($response['cardStatus']))
        {
            $inputData['status'] = $response['cardStatus'];
        }
        elseif (isset($response['hybridStatus']))
        {
            $inputData['status'] = $response['hybridStatus'];
        }

        return $inputData;
    }

    public function thankyou_page($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('kevin. Customer came back to thank you page', 'woocommerce-gateway-getkevin'));
    }

    /**
     * docs: https://docs.getkevin.eu/public/platform/v0.3#operation/getPaymentStatus
     * Function meant to resolve status groups given from getKevin api
     * Changes order status from getKevinStatus groups
     * Payment status groups:
     * started: Payment was started, nothing more.
     * pending: Payment is being processed.
     * completed: Payment is initiated and completed.
     * failed: For some reasons payment failed.
     * @param $order
     * @param $statusGroup
     */
    protected function resolveGetKevinStatusGroup($order, $statusGroup)
    {
        if ($statusGroup)
        {
            switch ($statusGroup)
            {
                case "pending":
                    $this->updateOrderStatus($order, $this->paymentStatusPending);
                    break;
                case "started":
                    $this->updateOrderStatus($order, $this->paymentStatusStarted);
                    break;
                case "completed":
                    $this->updateOrderStatus($order, $this->paymentStatusCompleted, $statusGroup);
                    break;
                case "failed":
                    $this->updateOrderStatus($order, $this->paymentStatusFailed);
                    break;
            }
        }
    }

    protected function updateOrderStatus($order, $status, $paymentStatus = "")
    {
        if (!$paymentStatus)
        {
            $paymentStatus = $status;
        }
        $orderStatusFiltered = str_replace("wc-", "", $status);
        if ($order->get_status() == $orderStatusFiltered)
        {
            $order->add_order_note(sprintf(__('kevin. Payment status is %s', 'woocommerce-gateway-getkevin'), $orderStatusFiltered));
        }
        $order->update_status(
            $orderStatusFiltered,
            __('kevin. Payment status changed to ', 'woocommerce-gateway-getkevin') . $paymentStatus . '<br />',
            true
        );
    }

    /**
     * Function needed to update order metadata for getKevin backend to work properly with order logic
     * handle_webhook function uses metadata to set needed information after payment was made.
     * @param $order
     * @param $response
     */
    protected function updateOrderMetaDataGetKevin($order, $response)
    {
        $order->update_meta_data('_kevin_id', $response['id']);
        $order->update_meta_data('_kevin_ip_address', $order->get_customer_ip_address());
        $order->update_meta_data('_kevin_ip_port', $this->get_customer_ip_port());
        $order->update_meta_data('_kevin_user_agent', $order->get_customer_user_agent());
        $order->update_meta_data('_kevin_status', $response['status']);
        $order->update_meta_data('_kevin_status_group', $response['statusGroup']);
        $order->save();

        $order->add_order_note(sprintf(__('kevin. Payment id is %s', 'woocommerce-gateway-getkevin'), $response['id']));
    }

    private function get_ui_locale()
    {
        $lang = explode('_', get_locale());
        $lang = reset($lang);
        if (!in_array($lang, $this->uiLocales))
        {
            return $this->uiLocaleDefault;
        }

        return $lang;
    }

    /**
     * @return string
     */
    private function get_customer_ip_port()
    {
        if (isset($_SERVER['HTTP_X_REAL_PORT']))
        {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_PORT']));
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PORT']))
        {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_PORT']));
        } elseif (isset($_SERVER['REMOTE_PORT']))
        {
            return sanitize_text_field(wp_unslash($_SERVER['REMOTE_PORT']));
        }

        return '';
    }

    public function generateTabs($tabs)
    {
        $data = [];
        foreach ($tabs as $key => $value)
        {
            $data[$key]['name'] = $value['name'];
            $data[$key]['slice'] = $this->generate_settings_html($value['slice'], false);
        }

        return $data;
    }

    public function order_received_text($text, $order)
    {
        if ($order && $this->id === $order->get_payment_method())
        {

            $paymentId = $order->get_meta('_kevin_id');
            $client = new WC_Kevin_Client($this->client_id, $this->client_secret, $this->clientOptions);

            $response = $client->getPaymentStatus($paymentId, []);

            if ($response['group'] == 'failed')
            {
                $paymentUrl = $order->get_checkout_payment_url();
                $html = "<div class='woocommerce-error'>" . __('Unfortunately, your order cannot be processed as the originating bank has declined your transaction. Please attempt your purchase again.', 'woocommerce-gateway-getkevin') . "</div></br>";
                $html .= "<a href='{$paymentUrl}' class='button pay'>" . __('Pay', 'woocommerce-gateway-getkevin') . "</a>";
                if (is_user_logged_in())
                {
                    $myAccountUrl = wc_get_page_permalink('myaccount');

                    $html .= "<a href='{$myAccountUrl}' class='button pay'>" . __('My account', 'woocommerce-gateway-getkevin') . "</a>";
                }
                return $html;
            }
            elseif($response['group'] == 'completed')
            {
                return "<div class='woocommerce-message'>" . __('Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you.', 'woocommerce-gateway-getkevin') . "</div>";
            }
            elseif($response['group'] == 'started')
            {
                return "<div class='woocommerce-message' style='background-color: #ffd144;border-color: #ffc000'>" . __('Payment initiation was cancelled. Please try again.', 'woocommerce-gateway-getkevin') . "</div>";
            }
            else
            {
                return "<div class='woocommerce-message' style='background-color: #ffd144;border-color: #ffc000'>" . __('We will start executing the order only after receiving the payment.', 'woocommerce-gateway-getkevin') . "</div>";
            }
        }

        return $text;
    }

    public function filter_woocommerce_order_fully_refunded_status($status, $orderId, $refundId)
    {
        return "";
    }
}
