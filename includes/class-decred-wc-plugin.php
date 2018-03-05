<?php

defined('ABSPATH') || exit;  // prevent direct URL execution

class DecredWcPlugin {
    
    private $name;
    private $operational;
    private $logger;
    
    public function __construct($name) {
        $this->name = $name;
        // Plugin can be active but not operational if requirements are not met.
        // Specifically if WooCommerce not active, we check that later.
        $this->operational = false;
    }
    
    public function init() {
        add_action('plugins_loaded',                     [$this,'callback_plugins_loaded'], 0);
        add_filter('woocommerce_payment_gateways',       [$this,'callback_add_payment_method']);
        add_filter('plugin_action_links_' . $this->name, [$this,'callback_action_links']); 
    }
    
    /**
     * Initializations that depend on previous plugins being loaded.
     * We specifically need the WooCommerce plugin classes (WC_*)
     */
    public function callback_plugins_loaded()
    { 
        // missing required WC classes, can't proceed
        if (!class_exists('WC_Payment_Gateway') || !class_exists('WC_Logger')) {
            return;
        }
        
        include_once 'class-wc-decred-payments.php';
        
        $this->logger = new WC_Logger();
        //$this->logger->debug(__METHOD__);
        
        $this->operational = true;
    }
    
    /**
     * Registers Decred payments class.
     * 
     * Settings form will show in WooCommerce Settings, Checkout tab.
     */
    public function callback_add_payment_method($methods)
    {
        if ($this->operational) {
            $methods[] = 'WC_Decred_Payments';
        }
        return $methods;
    }
    
    /**
     * Adds settings & logs links to the plugin entry in the plugins menu
     **/
    public function callback_action_links($links)
    {
        if (!$this->operational) {
            return $links;
        }
        
        $log_file = 'decred-' . sanitize_file_name( wp_hash( 'decred' ) ) . '-log';
        $logs_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file . '">Logs</a>';
        array_unshift($links, $logs_link);
        
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_decred_payments">Settings</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }
    
}