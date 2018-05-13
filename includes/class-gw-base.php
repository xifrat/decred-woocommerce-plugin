<?php
/**
 * Payment Gateway methods that conform a base to child classes
 *
 * Contains methods related to:
 * - initializations
 * - utilities that can be used at several stages
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\GW_Base
 * @extends     WC_Payment_Gateway
 * @version     0.1
 * @author      xifrat
 */
class GW_Base extends \WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->plugin             = $GLOBALS['decred_wc_plugin']; // dependency injection kind of.
		$this->id                 = Constant::CURRENCY_ID;
		$this->icon               = plugins_url( $this->plugin->name . Constant::ICON_PATH );
		$this->has_fields         = true;
		$this->method_title       = Constant::CURRENCY_NAME;
		$this->method_description = __( 'Allows direct payments with the Decred cryptocurrency.', 'decred' );
		$this->order_button_text  = __( 'Pay with Decred', 'decred' );

		$this->init_form_fields();

		$this->init_settings();
		$this->title        = $this->settings['title'];
		$this->instructions = $this->settings['instructions'];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_assets' ) );

		add_action( 'woocommerce_new_order', array( $this, 'wc_new_order' ) );

		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'thankyou_order_received_text' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'                 => array(
				'title'   => __( 'Enable/Disable', 'decred' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Decred direct payments', 'decred' ),
				'default' => 'no',
			),
			'title'                   => array(
				'title'       => __( 'Title', 'decred' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'decred' ),
				'default'     => Constant::CURRENCY_NAME,
				'desc_tip'    => true,
			),
			'master_public_key'       => array(
				'title'       => __( 'Master public key', 'decred' ),
				'type'        => 'text',
				'description' => __( 'Enter the master public key of the wallet you use to receive payments. Your wallet software should have an option to view it.', 'decred' ),
				'desc_tip'    => true,
			),
			'confirmations_to_wait'   => array(
				'title'       => __( 'Confirmations to wait', 'decred' ),
				'type'        => 'text',
				'description' => __( 'Once this number is reached the order status will be changed to "processing"', 'decred' ),
				'desc_tip'    => true,
				'default'     => '6',
			),
			'show_refund_address'     => array(
				'title'   => __( 'Show/Hide', 'decred' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show refund address at checkout', 'decred' ),
				'default' => 'yes',
			),
			'refund_address_optional' => array(
				'title'   => __( 'Optional/Required', 'decred' ),
				'type'    => 'checkbox',
				'label'   => __( 'Refund address at checkout is optional (if shown)', 'decred' ),
				'default' => 'yes',
			),
			'instructions'            => array(
				'title'       => __( 'Instructions', 'decred' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'decred' ),
				'desc_tip'    => true,
				'default'     => 'We will send an automated email once we have received your DCR payment.',
			),
		);
	}

	/**
	 * Tell WP to include links for required assets in the HTML <head>
	 */
	public function wp_enqueue_assets() {

		wp_enqueue_style( 'decred-styles', plugins_url( $this->plugin->name . Constant::STYLES_PATH ) );

	}

}
