<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Gateway_Kevin
 */
class WC_Gateway_Kevin extends WC_Payment_Gateway {
    private $client_id;
    private $client_secret;
    private $creditor_name;
    private $creditor_account;
    private $redirect_preferred;

    protected $paymentStatusStarted = 'started';
    protected $paymentStatusPending = 'pending';
    protected $paymentStatusCompleted = 'completed';
    protected $paymentStatusFailed = 'failed';

    protected $uiLocales = array( 'en', 'lt', 'lv', 'ee', 'fi', 'se', 'ru' );
    protected $uiLocaleDefault = 'en';

    public function __construct() {
        $this->id                 = 'kevin';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = 'kevin.';
        $this->method_description = 'kevin. is a payment infrastructure company which offers payment initiation service in EU&EEA.';

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled     = $this->get_option( 'enabled' );
        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        $this->client_id          = $this->get_option( 'client_id' );
        $this->client_secret      = $this->get_option( 'client_secret' );
        $this->creditor_name      = $this->get_option( 'creditor_name' );
        $this->creditor_account   = $this->get_option( 'creditor_account' );
        $this->redirect_preferred = $this->get_option( 'redirect_preferred' ) === 'yes';

        WC_Kevin_Client::set_credentials( $this->client_id, $this->client_secret );

        add_action( 'woocommerce_api_wc_gateway_kevin', array( $this, 'handle_webhook' ) );
        add_action( 'woocommerce_thankyou_kevin', array( $this, 'thankyou_page' ) );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ) );
    }

    public function init_form_fields() {
        $this->form_fields = require dirname( __FILE__ ) . '/admin/kevin-settings.php';
    }

    public function payment_fields() {

        $description  = $this->get_description();
        $country_code = WC()->customer->get_billing_country();
        $banks        = WC_Kevin_Client::get_banks( $country_code );

        ob_start();

        if ( $banks ) {
            echo '<div class="wc-kevin-banks-list">';

            foreach ( $banks as $bank ) {
                $html = '<div class="wc-kevin-bank">';
                $html .= '<p>';
                $html .= '<label>';
                $html .= '<input type="radio" name="payment[bank_id]" value="' . esc_attr( $bank['id'] ) . '" title="' . esc_attr( $bank['name'] ) . '">';
                $html .= '<img src="' . $bank['imageUri'] . '" height="48" alt="' . esc_attr( $bank['name'] ) . '">';
                $html .= esc_html( $bank['name'] );
                $html .= '</label>';
                $html .= '</p>';
                $html .= '</div>';
                echo $html;
            }

            echo '</div>';
        }

        if ( $description ) {
            echo '<div class="wc-kevin-banks-description">';
            echo apply_filters( 'wc_kevin_description', wpautop( wp_kses_post( $description ) ), $this->id );
            echo '</div>';
        }

        ob_end_flush();

        wp_register_style( 'kevin_styles', plugins_url( 'assets/css/kevin-styles.css', WC_KEVIN_MAIN_FILE ), array(), WC_KEVIN_VERSION );
        wp_enqueue_style( 'kevin_styles' );
    }

    public function validate_fields() {
        if ( empty( $_REQUEST['payment']['bank_id'] ) ) {
            wc_add_notice( __( 'Please select your bank.', 'woocommerce-gateway-kevin' ), 'error' );

            return false;
        }

        return true;
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        try {
            $redirect_url = $this->get_return_url( $order );
            $webhook_url  = WC()->api_request_url( 'WC_Gateway_Kevin' );

            $attr = [
                'redirectPreferred'       => $this->redirect_preferred,
                'Redirect-URL'            => $redirect_url,
                'Webhook-URL'             => $webhook_url,
                'endToEndId'              => strval( $order->get_id() ),
                'informationUnstructured' => sprintf( __( 'Order %s', 'woocommerce-gateway-kevin' ), $order->get_id() ),
                'currencyCode'            => $order->get_currency(),
                'amount'                  => number_format( $order->get_total(), 2, '.', '' ),
                'creditorName'            => $this->creditor_name,
                'creditorAccount'         => [
                    'iban' => $this->creditor_account,
                ],
            ];

            if ( ! empty( $_REQUEST['payment']['bank_id'] ) ) {
                $attr['bankId'] = $_REQUEST['payment']['bank_id'];
            }

            $response = WC_Kevin_Client::init_payment( $attr );

            $order->update_meta_data( '_kevin_id', $response['id'] );
            $order->update_meta_data( '_kevin_ip_address', $order->get_customer_ip_address() );
            $order->update_meta_data( '_kevin_status', $response['status'] );
            $order->update_meta_data( '_kevin_status_group', $response['statusGroup'] );

            $order->save();

            // Payment: Started
            $order->update_status( 'pending' );
            $order->add_order_note( sprintf( __( 'kevin. payment started (Payment ID: %s).', 'woocommerce-gateway-kevin' ), $response['id'] ) );

            wc_reduce_stock_levels( $order_id );
            WC()->cart->empty_cart();

            $response['confirmLink'] = add_query_arg( array( 'lang' => $this->get_ui_locale() ), $response['confirmLink'] );

            return array(
                'result'   => 'success',
                'redirect' => $response['confirmLink'],
            );
        } catch ( \Kevin\KevinException $e ) {
            $order->add_order_note( $e->getMessage() );
            $order->save();

            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }
    }

    public function handle_webhook() {
        if ( ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) ) {
            return;
        }

        $request_body  = file_get_contents( 'php://input' );
        $request_array = json_decode( $request_body, true );

        if ( is_array( $request_array ) ) {
            if ( empty( $request_array['id'] ) || empty( $request_array['status'] ) || empty( $request_array['statusGroup'] ) ) {
                return;
            }
            $request = $request_array;
        } else {
            if ( empty( $_POST['id'] ) || empty( $_POST['status'] ) || empty( $_POST['statusGroup'] ) ) {
                return;
            }

            $request = $_POST;
        }

        $response = [
            'id'          => $request['id'],
            'status'      => $request['status'],
            'statusGroup' => $request['statusGroup'],
        ];

        $attr   = array(
            'meta_query' => array(
                array(
                    'key'   => '_kevin_id',
                    'value' => esc_attr( $response['id'] )
                )
            )
        );
        $orders = wc_get_orders( $attr );

        if ( ! empty( $orders ) ) {
            $order          = $orders[0];
            $current_status = $order->get_meta( '_kevin_status_group' );

            // Ignore manually cancelled payments by user.
            if ( $response['statusGroup'] === $this->paymentStatusFailed && $response['status'] === 'CANC' ) {
                status_header( 200 );
                exit();
            }

            // Process only pending payments.
            if ( $current_status === $this->paymentStatusPending ) {
                // Process order status.
                if ( ! in_array( $order->get_status(), array( 'completed', 'failed' ) ) ) {
                    if ( $response['statusGroup'] === $this->paymentStatusCompleted ) {
                        // Payment: Complete
                        $order->payment_complete( $response['id'] );
                        $order->add_order_note( sprintf( __( 'kevin. payment complete (Payment ID: %s).', 'woocommerce-gateway-kevin' ), $response['id'] ) );

                        $order->update_meta_data( '_kevin_status', $response['status'] );
                        $order->update_meta_data( '_kevin_status_group', $response['statusGroup'] );

                        $order->save();

                        status_header( 200 );
                        exit();
                    }

                    if ( $response['statusGroup'] === $this->paymentStatusFailed ) {
                        // Payment: Failed
                        $order->update_status( 'failed' );
                        $order->add_order_note( __( 'kevin. payment failed.', 'woocommerce-gateway-kevin' ) );

                        $order->update_meta_data( '_kevin_status', $response['status'] );
                        $order->update_meta_data( '_kevin_status_group', $response['statusGroup'] );

                        $order->save();

                        status_header( 200 );
                        exit();
                    }
                }
            }

            // Ignore already completed or failed orders.
            if ( in_array( $current_status, array( $this->paymentStatusCompleted, $this->paymentStatusFailed ) ) ) {
                status_header( 200 );
                exit();
            }

            // Payment status did not match any cases.
            status_header( 400 );
            exit();
        }

        // Payment id was not found or cancelled by user (replaced by new payment id).
        status_header( 200 );
        exit();
    }

    public function thankyou_page( $order_id ) {
        $order      = wc_get_order( $order_id );
        $payment_id = ! empty( $_GET['paymentId'] ) ? $_GET['paymentId'] : null;

        if ( $payment_id && $payment_id === $order->get_meta( '_kevin_id' ) ) {
            if ( $order->get_meta( '_kevin_status_group' ) === $this->paymentStatusStarted ) {
                try {
                    $response = WC_Kevin_Client::get_payment_status( $payment_id, array( 'PSU-IP-Address' => $order->get_meta( '_kevin_ip_address' ) ) );

                    if ( $response['group'] === $this->paymentStatusFailed ) {
                        if ( $response['status'] === 'CANC' ) {
                            // Order cancelled by user
                            $order->add_order_note( __( 'kevin. payment cancelled by user.', 'woocommerce-gateway-kevin' ) );

                            wp_safe_redirect( $order->get_checkout_payment_url( false ) );
                            exit();
                        } else {
                            // Payment: Failed
                            $order->update_status( 'failed' );
                            $order->add_order_note( __( 'kevin. payment failed.', 'woocommerce-gateway-kevin' ) );

                            $order->update_meta_data( '_kevin_status', $response['status'] );
                            $order->update_meta_data( '_kevin_status_group', $response['statusGroup'] );

                            $order->save();

                            wp_safe_redirect( $order->get_cancel_order_url() );
                            exit();
                        }
                    }

                    if ( $order->get_meta( '_kevin_status_group' ) !== $this->paymentStatusPending ) {
                        // Payment: Processing
                        $order->update_status( 'on-hold' );
                        $order->add_order_note( __( 'kevin. payment processing.', 'woocommerce-gateway-kevin' ) );

                        // Set payment status group to 'pending' and let webhook handle the rest.
                        $order->update_meta_data( '_kevin_status', $response['status'] );
                        $order->update_meta_data( '_kevin_status_group', $this->paymentStatusPending ); // $response['group']

                        $order->save();
                    }
                } catch ( \Kevin\KevinException $e ) {
                    $order->add_order_note( $e->getMessage() );
                }
            }
        }
    }

    private function get_ui_locale() {
        $lang = explode( '_', get_locale() );
        $lang = reset( $lang );
        if ( ! in_array( $lang, $this->uiLocales ) ) {
            return $this->uiLocaleDefault;
        }

        return $lang;
    }
}
