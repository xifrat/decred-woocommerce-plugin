<?php
/**
 * Payment Gateway methods used by the checkout page
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

require_once __DIR__ . '/class-constant.php';

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\GW_Checkout
 * @extends     GW_Base
 * @version     0.1
 * @author      xifrat
 */
class GW_Checkout extends GW_Base {

	/**
	 * Show refund address in checkout page?
	 */
	public function show_refund_address() {
		return $this->get_option( 'show_refund_address', 'no' ) == 'yes';
	}

	/**
	 * Make the refund address a required field?
	 */
	public function require_refund_address() {
		return $this->show_refund_address() && $this->get_option( 'refund_address_optional', 'yes' ) == 'no';
	}

	/**
	 *  TODO convert order fiat amount into DCR
	 */
	public function convert_to_dcr() {
		$this->dcr_amount = 3.4507890;
	}

	/**
	 * HTML form with payment fields
	 */
	public function payment_fields() {

		$this->convert_to_dcr();
		$this->format_amount();

		require __DIR__ . '/html-checkout.php';
	}

	/**
	 * Safely get post data if set
	 *
	 * @param string $name name of post argument to get.
	 * @return mixed post data, or null.
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return trim( $_POST[ $name ] );
		}
		return null;
	}

	/**
	 * Validate HTML form fields
	 */
	public function validate_fields() {
		$address = $this->get_post( 'decred-refund-address' );

		// this should never happen as the refund address field should be missing.
		if ( ! empty( $address ) && ! $this->show_refund_address() ) {
			wc_add_notice( __( 'Decred plugin error: unexpected refund address.', 'decred' ), 'error' );
			return false;
		}

		// empty address OK if optional, otherwise validate it.
		if ( empty( $address ) && ! $this->require_refund_address() ) {
			$address_is_valid = true;
		} else {
			$address_is_valid = $this->validate_refund_address( $address );
		}

		if ( ! $address_is_valid ) {
			wc_add_notice( __( 'Please enter a valid Decred address for refunds.', 'decred' ), 'error' );
		}
		return $address_is_valid;
	}

	/**
	 *
	 * Verify if the refund address is a valid Decred one. TODO implement fully.
	 *
	 * @param string $address Decred refund address.
	 * @return boolean
	 */
	public function validate_refund_address( $address ) {
		return strlen( $address ) == 35;
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

}
