<?php

defined('ABSPATH') or exit;

/**
 * Admin settings
 */
class Wc_GetKevin_Settings extends WC_Payment_Gateway
{
    /**
     * @var array
     */
    protected $formFields;

    /**
     * Wc_GetKevin_Settings constructor.
     */
    public function __construct()
    {
        $this->formFields = [
            'enabled'            => [
                'title'       => __( 'Enable/Disable', 'woocommerce-gateway-getkevin' ),
                'type'        => 'checkbox',
                'description' => '',
                'label'       => __( 'Enable kevin.', 'woocommerce-gateway-getkevin' ),
                'default'     => 'yes'
            ],
            'title'              => [
                'title'       => __( 'Title', 'woocommerce-gateway-getkevin' ),
                'type'        => 'text',
                'css'               => 'width: 420px;',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-getkevin' ),
                'default'     => __( 'kevin.', 'woocommerce-gateway-getkevin' ),
                'desc_tip'    => true,
                'custom_attributes' => ['required' => 'required']

            ],
            'description'        => [
                'title'       => __( 'Description', 'woocommerce-gateway-getkevin' ),
                'type'        => 'textarea',
                'css'               => 'width: 420px;',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-getkevin' ),
                'desc_tip'    => true,
                'placeholder' => __('Choose a payment method via kevin.', 'woocommerce-gateway-getkevin'),
            ],
            'client_id'          => [
                'title'       => __( 'Client ID', 'woocommerce-gateway-getkevin' ),
                'type'        => 'text',
                'css'               => 'width: 420px;',
                'description' => __( 'Your client id. Your can get it in kevin. platform console.', 'woocommerce-gateway-getkevin' ),
                'desc_tip'    => true,
                'default'     => '',
                'custom_attributes' => ['required' => 'required']
            ],
            'client_secret'      => [
                'title'       => __( 'Client Secret', 'woocommerce-gateway-getkevin' ),
                'type'        => 'text',
                'css'               => 'width: 420px;',
                'description' => __( 'Your client secret. Your can get it in kevin. platform console.', 'woocommerce-gateway-getkevin' ),
                'desc_tip'    => true,
                'default'     => '',
                'custom_attributes' => ['required' => 'required']
            ],
            'creditor_name'      => [
                'title'       => __( 'Company Name', 'woocommerce-gateway-getkevin' ),
                'type'        => 'text',
                'css'               => 'width: 420px;',
                'description' => '',
                'default'     => '',
                'custom_attributes' => ['required' => 'required']
            ],
            'creditor_account'   => [
                'title'       => __( 'Company Bank Account', 'woocommerce-gateway-getkevin' ),
                'type'        => 'text',
                'css'               => 'width: 420px;',
                'description' => '',
                'default'     => '',
                'custom_attributes' => ['required' => 'required']
            ],
            'redirect_preferred' => [
                'title'       => __( 'Redirect Preferred', 'woocommerce-gateway-getkevin' ),
                'type'        => 'checkbox',
                'description' => '',
                'label'       => __( 'Redirect user directly to bank.', 'woocommerce-gateway-getkevin' ),
                'default'     => 'yes'
            ],
            'paymentNewOrderStatus' => array(
                'title'       => __('New order status', 'woocommerce-gateway-getkevin'),
                'type'        => 'select',
                'class'	      => 'wc-enhanced-select',
                'default'     => 'wc-processing',
                'description' => __('Status of a new order that has been created', 'woocommerce-gateway-getkevin'),
                'options'     => array(),
            ),
            'paymentCompletedOrderStatus' => array(
                'title'       => __('Paid order status', 'woocommerce-gateway-getkevin'),
                'type'        => 'select',
                'class'	      => 'wc-enhanced-select',
                'default'     => 'wc-processing',
                'description' => __('Status of an order that has been successfully paid.', 'woocommerce-gateway-getkevin'),
                'options'     => array(),
            ),
            'paymentPendingOrderStatus' => array(
                'title'       => __('Pending checkout', 'woocommerce-gateway-getkevin'),
                'type'        => 'select',
                'class'	      => 'wc-enhanced-select',
                'default'     => 'wc-pending',
                'description' => __('Status of an order that has not been paid yet.', 'woocommerce-gateway-getkevin'),
                'options'     => array(),
            ),
        ];
    }

    /**
     * @param object $tabs
     * @param boolean [Optional] $print
     *
     * @return boolean|string
     */
    public function buildAdminFormHtml($tabs, $print = true)
    {
        $htmlData = $this->generateFormFields($tabs);

        $html  = '<div class="getKevin_config">';
        $html .= '<h2>' . $htmlData['links'] . '</h2>';
        $html .= '<div style="clear:both;"><hr /></div>';
        $html .= $htmlData['tabs'];
        $html .= '</div>';

        if ($print) {
            print_r($html);
            return $print;
        } else {
            return $html;
        }
    }

    /**
     * @return array
     */
    protected function getStatusList()
    {
        $wcStatus = array_keys(wc_get_order_statuses());
        $orderStatus = array();
        foreach ($wcStatus as $key => $value) {
            $orderStatus[$wcStatus[$key]] = wc_get_order_status_name($wcStatus[$key]);
        }

        return $orderStatus;
    }

    /**
     * @param object $tabs
     *
     * @return array
     */
    protected function generateFormFields($tabs)
    {
        $tabsLink = '';
        $tabsContent = '';
        foreach ($tabs as $key => $value) {
            $tabsLink .= '<a href="javascript:void(0)"';
            $tabsLink .= ' id="tab' . $key . '" class="nav-tab"';
            $tabsLink .= ' data-cont="content' . $key . '">';
            $tabsLink .=  $value['name'] . '</a>';

            $tabsContent .= '<div id="content' . $key . '" class="tabContent">';
            $tabsContent .= '<table class="form-table">' . $value['slice'] . '</table>';
            $tabsContent .= '</div>';
        }

        return [
            'links' => $tabsLink,
            'tabs'  => $tabsContent
        ];
    }

    /**
     * @return array
     */
    public function getFormFields()
    {
        return $this->formFields;
    }


    public function updateAdminSettings($form_fields)
    {
        $form_fields['paymentNewOrderStatus']['options']  = $this->getStatusList();
        $form_fields['paymentCompletedOrderStatus']['options'] = $this->getStatusList();
        $form_fields['paymentPendingOrderStatus']['options']  = $this->getStatusList();

        return $form_fields;
    }


}
