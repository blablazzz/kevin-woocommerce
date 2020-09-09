<?php

defined( 'ABSPATH' ) || exit;

return apply_filters(
    'wc_kevin_settings',
    array(
        'enabled'            => array(
            'title'       => __( 'Enable/Disable', 'woocommerce-gateway-kevin' ),
            'type'        => 'checkbox',
            'description' => '',
            'label'       => __( 'Enable kevin.', 'woocommerce-gateway-kevin' ),
            'default'     => 'yes'
        ),
        'title'              => array(
            'title'       => __( 'Title', 'woocommerce-gateway-kevin' ),
            'type'        => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-kevin' ),
            'default'     => __( 'Bank Payment', 'woocommerce-gateway-kevin' ),
            'desc_tip'    => true,
        ),
        'description'        => array(
            'title'       => __( 'Description', 'woocommerce-gateway-kevin' ),
            'type'        => 'textarea',
            'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-kevin' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'client_id'          => array(
            'title'       => __( 'Client ID', 'woocommerce-gateway-kevin' ),
            'type'        => 'text',
            'description' => '',
            'default'     => '',
        ),
        'client_secret'      => array(
            'title'       => __( 'Client Secret', 'woocommerce-gateway-kevin' ),
            'type'        => 'text',
            'description' => '',
            'default'     => '',
        ),
        'creditor_name'      => array(
            'title'       => __( 'Company Name', 'woocommerce-gateway-kevin' ),
            'type'        => 'text',
            'description' => '',
            'default'     => '',
        ),
        'creditor_account'   => array(
            'title'       => __( 'Company Bank Account', 'woocommerce-gateway-kevin' ),
            'type'        => 'text',
            'description' => '',
            'default'     => '',
        ),
        'redirect_preferred' => array(
            'title'       => __( 'Redirect Preferred', 'woocommerce-gateway-kevin' ),
            'type'        => 'checkbox',
            'description' => '',
            'label'       => __( 'Redirect user directly to bank.', 'woocommerce-gateway-kevin' ),
            'default'     => 'yes'
        ),
    )
);
