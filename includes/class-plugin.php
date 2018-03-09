<?php
/**
 *  Plugin Intialization
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 *
 * Class created mostly to group together plugin initialization code.
 *
 * @author xifrat
 */
class Plugin {

	/**
	 * Internal WordPress plugin name
	 *
	 * @var string name
	 */
	public $name;

	/**
	 * Is the plugin operational?
	 *
	 * Plugin can be active but not operational if requirements are not met.
	 * Specifically if the WooCommerce plugin is not active (we check this after plugins loaded).
	 *
	 * @var boolean operational
	 */
	public $operational;

	/**
	 * Logger class, set after plugins loaded.
	 *
	 * @var WC_Logger operational
	 */
	public $logger;

	/**
	 * Constructor, de facto singleton, but we don't bother enforcing it.
	 *
	 * @param string $name Internal WordPress plugin name.
	 */
	public function __construct( $name ) {
		$this->name        = $name;
		$this->operational = false;
	}

	/**
	 * Intialize plugin (set callbacks).
	 */
	public function init() {
		add_action( 'plugins_loaded', [ $this, 'callback_plugins_loaded' ], 0 );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'callback_add_payment_method' ] );
		add_filter( 'plugin_action_links_' . $this->name, [ $this, 'callback_action_links' ] );
	}

	/**
	 * Initializations that depend on previous plugins being loaded.
	 * We specifically need a number of WooCommerce plugin classes (WC_*).
	 */
	public function callback_plugins_loaded() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) || ! class_exists( 'WC_Logger' ) ) {
			return; // missing required WC classes, can't proceed.
		}

		require_once 'class-gateway.php';

		$this->logger = new \WC_Logger();

		$this->operational = true;
	}

	/**
	 * Registers Decred payments class.
	 *
	 * Settings form will show in WooCommerce Settings, Checkout tab.
	 *
	 * @param array $methods payment methods active in WooCommerce.
	 */
	public function callback_add_payment_method( $methods ) {
		if ( $this->operational ) {
			$methods[] = 'Decred\Payments\WooCommerce\Gateway';
		}
		return $methods;
	}

	/**
	 * Adds settings & logs links to the plugin entry in the plugins menu.
	 *
	 * @param array $links links already set by WordPress.
	 **/
	public function callback_action_links( $links ) {
		if ( ! $this->operational ) {
			return $links;
		}

		$log_file  = 'decred-' . sanitize_file_name( wp_hash( 'decred' ) ) . '-log';
		$logs_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file . '">Logs</a>';
		array_unshift( $links, $logs_link );

		$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_decred_payments">Settings</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

}
