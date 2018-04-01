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
	 * Convert fiat amount to DCR
	 *
	 * @param string $currency fiat currency code (ISO 4217).
	 * @param float  $amount fiat amount to convert.
	 *
	 * @return float amount in DCR.
	 * @throws \Exception TODO catch.
	 */
	public function convert_to_dcr( $currency, $amount ) {
		return \Decred\Rate\CoinMarketCap::getRate( $currency )->convertToCrypto( $amount );
	}

	/**
	 * HTML form with payment fields
	 *
	 * TODO error management
	 */
	public function payment_fields() {
		try {

			$this->dcr_amount = $this->get_dcr_amount();
			require __DIR__ . '/html-checkout.php';

		} catch ( \Exception $e ) {
			// TODO log $e.
			printf(
				// translators: don't translate error message.
				esc_html__( 'There was an error while trying to get the DCR amount: "%s".', 'decred' ),
				$e->getMessage()
			);
		}
	}

	/**
	 * Get cart total amount converted into DCR
	 *
	 * @throws \Exception Any error that prevents returning an accurate amount.
	 * @return float DCR amount.
	 */
	private function get_dcr_amount() {
		if ( ! function_exists( 'get_woocommerce_currency' ) ) {
			throw new \Exception( 'get_woocommerce_currency() does not exist' );
		}
		$currency = $this->get_currency();

		global $woocommerce;
		if ( ! is_object( $woocommerce->cart ) || ! method_exists( $woocommerce->cart, 'get_total' ) ) {
			throw new \Exception( 'global $woocommerce->cart->get_total() does not exist' );
		}
		$amount = $woocommerce->cart->get_total( 'unformatted' );
		if ( ! is_numeric( $amount ) || ! $amount > 0 ) {
			throw new \Exception( 'total cart amount not a positive number' );
		}

		return $this->convert_to_dcr( $currency, $amount ); // may also throw exceptions.
	}

	/**
	 * Get WC currency. Wraps global function to allow testing by overriding this method.
	 *
	 * @return string currency code.
	 */
	protected function get_currency() {
		return get_woocommerce_currency();
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
