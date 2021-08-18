<?php
/**
 * Plugin Name: WooCommerce Gateway - kevin.
 * Plugin URI: https://www.kevin.eu/
 * Description: kevin. is a payment infrastructure company which offers payment initiation service in EU&EEA.
 * Version: 2.1.4
 * Requires at least: 5.2
 * Tested up to: 5.5
 * Requires PHP: 5.6
 * Author: kevin.
 * Author URI: https://www.kevin.eu/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: woocommerce-gateway-getkevin
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 4.4
 */

defined( 'ABSPATH' ) || exit;

define('WCGatewayGetKevinPluginUrl', plugin_dir_url(__FILE__));
define('WCGatewayGetKevinPluginPath', basename(dirname( __FILE__ )));
define( 'WC_KEVIN_MAIN_FILE', __FILE__ );
define('WC_KEVIN_VERSION', '2.1.4');

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

require_once "includes/class-wc-getkevin-load.php";

(new Wc_GetKevin_Load())->load();
