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
		$this->icon               = plugins_url( Constant::ICON_PATH, $this->plugin->name );
		$this->has_fields         = true;
		$this->method_title       = Constant::CURRENCY_NAME;
		$this->method_description = __( 'Allows direct payments with the Decred cryptocurrency.', 'decred' );
		$this->order_button_text  = __( 'Pay with Decred', 'decred' );

		$this->init_form_fields();

		$this->init_settings();
		$this->title        = $this->settings['title'];
		$this->description  = $this->settings['description'];
		$this->instructions = $this->settings['instructions'];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'woocommerce_new_order', array( $this, 'woocommerce_new_order' ) );

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
			'description'             => array(
				'title'       => __( 'Description', 'decred' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'decred' ),
				'default'     => __( 'Please send some specific Decred amount to the address we provide here.', 'decred' ),
				'desc_tip'    => true,
			),
			'instructions'            => array(
				'title'       => __( 'Instructions', 'decred' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'decred' ),
				'default'     => '',
				'desc_tip'    => true,
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
		);
	}

	/**
	 * Tell WP to include links for required assets in the HTML <head>
	 */
	public function enqueue_assets() {

		$i      = 1;
		$handle = 'decred-';

		/*
		 * TODO implement JS features
		 *
		foreach( Constant::JS_PATHS as $js_path ) {
			$src = plugins_url( $js_path, $this->plugin->name );
			wp_enqueue_script( $handle . $i, $src );
			$i++;
		}
		*/

		$src = plugins_url( Constant::STYLES_PATH, $this->plugin->name );
		wp_enqueue_style( $handle . $i, $src );
	}

}
