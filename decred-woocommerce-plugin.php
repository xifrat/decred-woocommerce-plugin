<?php
/**
 *
 * Plugin Name: Decred direct payments for WooCommerce
 * Plugin URI: https://github.com/xifrat/decred-woocommerce-plugin
 * Description: Accept the Decred cryptocurrency on your WooCommerce store. Not a gateway, customers will send DCR directly to your wallet.
 * Version: 0.1
 * Author: xifrat
 * Author URI:  https://github.com/xifrat
 * Text Domain: decred
 * License: ISC
 * License URI: https://github.com/xifrat/decred-woocommerce-plugin/blob/master/LICENSE
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit; // prevent direct URL execution.

global $decred_wc_plugin; // global makes testing easier.

require_once __DIR__ . '/includes/class-plugin.php';

$decred_wc_plugin = new Plugin( __FILE__ );

$decred_wc_plugin->init();
