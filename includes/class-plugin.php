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
	 * Absolute path to the plugin loading file
	 *
	 * @var string file
	 */
	public $file;


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
	 * @param string $file plugin file path.
	 */
	public function __construct( $file ) {
		$this->file = $file;
		// plugins' file name without the ".php" suffix.
		$this->name        = substr( basename( $file ), 0, -4 );
		$this->operational = false;
	}

	/**
	 * Minimal initializations, as most of them require WooCommerce plugin loaded.
	 */
	public function init() {
		register_activation_hook( $this->name, [ $this, 'callback_activation_hook' ] );
		add_action( 'plugins_loaded', [ $this, 'callback_plugins_loaded' ], 0 );
	}

	/**
	 * Plugin activation hook. Verify requirements.
	 */
	public function callback_activation_hook() {
		if ( ! extension_loaded( 'gmp' ) ) {
			wp_die( __( 'PHP\'s GMP extension missing, this plugin requires it.', 'decred' ) );
		}
	}
	/**
	 * Initializations that depend on previous plugins being loaded.
	 * We specifically need a number of WooCommerce plugin classes (WC_*).
	 */
	public function callback_plugins_loaded() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) || ! class_exists( 'WC_Logger' ) ) {
			return; // missing required WC classes, can't proceed.
		}

		// required for Decred PHP API.
		if ( ! extension_loaded( 'gmp' ) ) {
			return;
		}

		$files = [
			'class-api-helper.php',
			'class-constant.php',
			'class-gw-base.php',
			'class-gw-checkout.php',
			'class-gw-thankyou.php',
			'class-gateway.php',
		];
		foreach ( $files as $file ) {
			require_once __DIR__ . "/$file";
		}

		// Autoload Decred PHP API and its dependencies via composer.
		require_once dirname( __DIR__ ) . '/vendor/autoload.php';

		$this->complete_init();
	}

	/**
	 * Intializations once WooCommerce plugin is loaded.
	 */
	public function complete_init() {

		add_filter( 'woocommerce_payment_gateways', [ $this, 'callback_add_payment_method' ] );
		add_filter( 'plugin_action_links', [ $this, 'wp_action_links' ] );
		add_filter( 'cron_schedules', [ $this, 'wp_add_schedule' ] );

		add_action( 'decred_order_status_updater', [ $this, 'order_status_updater' ] );

		add_action( 'wp_ajax_decred_order_status', [ $this, 'ajax_order_status' ] );

		$this->logger      = new \WC_Logger();
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
	public function wp_action_links( $links ) {
		if ( ! $this->operational ) {
			return $links;
		}

		$log_file  = 'decred-' . sanitize_file_name( wp_hash( 'decred' ) ) . '-log';
		$logs_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file . '">Logs</a>';
		array_unshift( $links, $logs_link );

		$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=decred">Settings</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add a WP-Cron schedule to be used by the order status updater.
	 *
	 * @param array $schedules currently defined intervals.
	 * @return array $schedules with new element.
	 */
	public function wp_add_schedule( $schedules ) {
		// translators: parameter is a number.
		$text = esc_html__( 'Decred order status updater, every %s seconds', 'decred' );
		$text = sprintf( $text, Constant::CRON_INTERVAL );

		$schedules['decred_schedule'] = array(
			'interval' => Constant::CRON_INTERVAL,
			'display'  => $text,
		);
		return $schedules;
	}

	/**
	 * Order status updater: load & execute. To be called by WP-Cron.
	 */
	public function order_status_updater() {
		require_once __DIR__ . '/class-status-updater.php';
		$updater = new Status_Updater();
		$updater->execute();
	}

	/**
	 * Aja order status
	 */
	public function ajax_order_status() {
		$result = [];

		/** @var \WC_Order $order */
		/** @var \WP_User $user */
		if ( isset( $_GET['order_id'] ) && is_numeric( $_GET['order_id'] ) ) {
			$order = wc_get_order( $_GET['order_id'] );
			$user  = wp_get_current_user();

			if ( $order->get_user_id() === $user->ID ) {
				$result['status'] = $order->get_status();
				$result['txid']   = get_post_meta( $order->get_id(), 'decred_txid', true );
			}
		}

		echo json_encode( $result );
		exit;
	}

	// @codingStandardsIgnoreLine
	public function tmp_log( $msg ) {
		file_put_contents(
			'/tmp/order_status_updater.log',
			date( 'c' ) . " $msg \n",
			FILE_APPEND
		);
	}

}
