<?php
/*
 Plugin Name: Direct Decred Payments for WooCommerce
 Plugin URI: https://github.com/xifrat/decred-woocommerce-plugin
 Description: Accept the Decred cryptocurrency on your WooCommerce store. Not a gateway, customers will send DCR directly to your wallet.
 Version: 0.1
 Author: xifrat
 Author URI:  https://github.com/xifrat
 Text Domain: decred
 License: ISC
 License URI: https://github.com/xifrat/decred-woocommerce-plugin/blob/master/LICENSE
 */

defined( 'ABSPATH' ) || exit; // prevent direct URL execution

// Ensures WooCommerce is loaded before initializing the plugin
add_action('plugins_loaded', 'decred_payments_init', 0);

// Include the main class
function decred_payments_init()
{
    if (class_exists('WC_Decred_Payments') || !class_exists('WC_Payment_Gateway')) {
        return;
    }
    include_once dirname( __FILE__ ) . '/includes/class-wc-decred-payments.php';
}

/**
 * Add Decred Payments to WooCommerce
 **/
function wc_add_decred_payments($methods)
{
    $methods[] = 'WC_Decred_Payments';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'wc_add_decred_payments');

/**
 * Add Settings link to the plugin entry in the plugins menu
 * 
 * Attribution: this portion taken from https://github.com/bitpay/woocommerce-plugin
 * 
 **/
add_filter('plugin_action_links', 'decred_plugin_action_links', 10, 2);

function decred_plugin_action_links($links, $file)
{
    static $this_plugin;
    
    if (false === isset($this_plugin) || true === empty($this_plugin)) {
        $this_plugin = plugin_basename(__FILE__);
    }
    
    if ($file == $this_plugin) {
        $log_file = 'decred-' . sanitize_file_name( wp_hash( 'decred' ) ) . '-log';
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_decred_payments">Settings</a>';
        $logs_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file . '">Logs</a>';
        array_unshift($links, $settings_link, $logs_link);
    }
    
    return $links;
}