<?php
/**
 * Plugin Name: WooCommerce Gateway - kevin.
 * Plugin URI: https://www.getkevin.eu/
 * Description: kevin. is a payment infrastructure company which offers payment initiation service in EU&EEA.
 * Version: 1.1.1
 * Requires at least: 5.2
 * Tested up to: 5.5
 * Requires PHP: 5.6
 * Author: kevin.
 * Author URI: https://www.getkevin.eu/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: woocommerce-gateway-kevin
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 4.4
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_KEVIN_VERSION', '1.1.1' );

define( 'WC_KEVIN_MIN_PHP_VER', '5.6.0' );
define( 'WC_KEVIN_MIN_WC_VER', '4.0.0' );

define( 'WC_KEVIN_MAIN_FILE', __FILE__ );

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

/**
 * Return WooCommerce requirement notice.
 */
function woocommerce_kevin_wc_is_missing() {
    echo '<div class="error"><p><strong>' . __( 'kevin. gateway requires active WooCommerce plugin.', 'woocommerce-gateway-kevin' ) . '</strong></p></div>';
}

/**
 * Return WooCommerce version notice.
 */
function woocommerce_kevin_wc_not_supported() {
    echo '<div class="error"><p><strong>' . sprintf( __( 'kevin. gateway requires WooCommerce plugin version %1$s or greater. You are running %2$s.', 'woocommerce-gateway-kevin' ), WC_KEVIN_MIN_WC_VER, WC()->version ) . '</strong></p></div>';
}

/**
 * Return PHP version notice.
 */
function woocommerce_kevin_php_not_supported() {
    echo '<div class="error"><p><strong>' . sprintf( __( 'kevin. gateway requires PHP version %1$s or greater. You are running %2$s.', 'woocommerce-gateway-kevin' ), WC_KEVIN_MIN_PHP_VER, phpversion() ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_gateway_kevin_init' );

function woocommerce_gateway_kevin_init() {
    load_plugin_textdomain( 'woocommerce-gateway-kevin', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'woocommerce_kevin_wc_is_missing' );

        return;
    }

    if ( version_compare( WC()->version, WC_KEVIN_MIN_WC_VER, '<' ) ) {
        add_action( 'admin_notices', 'woocommerce_kevin_wc_not_supported' );

        return;
    }

    if ( version_compare( phpversion(), WC_KEVIN_MIN_PHP_VER, '<' ) ) {
        add_action( 'admin_notices', 'woocommerce_kevin_php_not_supported' );

        return;
    }

    if ( ! class_exists( 'WC_Kevin' ) ) {
        class WC_Kevin {
            private static $instance;

            public function __construct() {
                $this->init();
            }

            public function init() {
                require_once dirname( __FILE__ ) . '/includes/class-wc-kevin-client.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-kevin.php';

                add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
                add_filter( 'plugin_action_links_' . plugin_basename( WC_KEVIN_MAIN_FILE ), array( $this, 'plugin_action_links' ) );
            }

            public function add_gateways( $methods ) {
                $methods[] = 'WC_Gateway_Kevin';

                return $methods;
            }

            public function plugin_action_links( $links ) {
                $plugin_links = array( '<a href="admin.php?page=wc-settings&tab=checkout&section=kevin">' . esc_html__( 'Settings', 'woocommerce-gateway-kevin' ) . '</a>', );

                return array_merge( $plugin_links, $links );
            }

            public static function instance() {
                if ( null === self::$instance ) {
                    self::$instance = new self();
                }

                return self::$instance;
            }
        }

        WC_Kevin::instance();
    }
}
