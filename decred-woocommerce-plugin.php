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
 *
 * @package Decred Payments
 */

defined( 'ABSPATH' ) || exit; // prevent direct URL execution.

include_once 'includes/class-decred-wc-plugin.php';

$decred_plugin_name = plugin_basename( __FILE__ );

$decred_wc_plugin = new Decred_WC_Plugin( $decred_plugin_name );

$decred_wc_plugin->init();
