<?php
/**
 * Payment Gateway class as required by WooCommerce
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

require_once 'class-constant.php';

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\Gateway
 * @extends     WC_Payment_Gateway
 * @version     0.1
 * @author      xifrat
 */
class Gateway extends \WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->plugin             = $GLOBALS['decred_wc_plugin']; // dependency injection kindof
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
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'decred' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Decred direct payments', 'decred' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'decred' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'decred' ),
				'default'     => Constant::CURRENCY_NAME,
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'decred' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'decred' ),
				'default'     => __( 'Please send some specific Decred amount to the address we provide here.', 'decred' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'decred' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'decred' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}
	
	public function enqueue_assets() {
		
		$i = 1;
		$handle = 'decred-';
		
		/* TODO implement JS features
		foreach( Constant::JS_PATHS as $js_path ) {
			$src = plugins_url( $js_path, $this->plugin->name );
			wp_enqueue_script( $handle . $i, $src );
			$i++;
		}
		*/

		$src = plugins_url( Constant::STYLES_PATH, $this->plugin->name );
		wp_enqueue_style( $handle . $i, $src );
	}
	
	/**
	 * HTML form with payment fields
	 */
	public function payment_fields() {
		
		$dcr_amount = 3.4507890; // TODO get amount from order, convert to DCR
		
		require_once 'form-checkout.php';
	}	
	
	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wpautop( wptexturize( $this->instructions ) );
		}
		require_once 'html-thankyou.php';	
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order .
	 * @param bool     $sent_to_admin .
	 * @param bool     $plain_text .
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'decred' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id .
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Mark as on-hold (we're awaiting the cheque).
			$order->update_status( 'on-hold', __( 'Awaiting Decred payment', 'decred' ) );
		} else {
			$order->payment_complete();
		}

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
