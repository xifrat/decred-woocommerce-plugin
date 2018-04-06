<?php
/**
 * Payment Gateway methods used by the checkout page
 */

namespace Decred\Payments\WooCommerce;

use Decred\Crypto\ExtendedKey;

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
	 * DCR amount to show in checkout & thankyou pages.
	 *
	 * @var float dcr_amount
	 */
	public $dcr_amount;

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

		WC()->session->set( 'decred_amount', null );

		try {

			$this->dcr_amount = $this->get_dcr_amount();

			// save amount now to retrieve it later when order created.
			WC()->session->set( 'decred_amount', $this->dcr_amount );

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
	 *
	 * Note that errors are signaled by a second parameter 'error' in wc_add_notice().
	 * If no such call is made WC assumes the validations passed.
	 */
	public function validate_fields() {

		$this->validate_refund_address_field();

		$this->obtain_payment_address();
	}

	/**
	 * Validate refund address field
	 *
	 * - verify field is not set if not shown
	 * - verify field is set if required
	 * - if set verify it's a valid address
	 */
	public function validate_refund_address_field() {

		WC()->session->set( 'decred_refund_address', null );

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

		if ( $address_is_valid ) {
			if ( ! empty( $address ) ) {
				// save address now to retrieve it later when order created.
				WC()->session->set( 'decred_refund_address', $address );
			}
		} else {
			wc_add_notice( __( 'Please enter a valid Decred address for refunds.', 'decred' ), 'error' );
		}
	}

	/**
	 * Verify that an address is a valid Decred one.
	 *
	 * Currently:
	 * - length 35
	 * - initial two characters 'Ds' (mainnet) or 'Ts' (testnet)
	 *
	 * TODO futher validation of the third+ characters.
	 *
	 * @param string $address Decred address.
	 * @return boolean
	 */
	public function validate_address( $address ) {
		// @codingStandardsIgnoreStart
		return strlen( $address ) == 35
			&& ( $address[0] == 'D' || $address[0] == 'T' )
			&& $address[1] == 's';
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Obtain a new Decred payment address that will be shown in the thankyou page.
	 * Don't proceed to the thankyou page if some error is detected.
	 */
	public function obtain_payment_address() {

		try {
			$address = $this->get_new_payment_address( 1 );

			// save address now to retrieve it later when order created.
			WC()->session->set( 'decred_payment_address', $address );

		} catch ( \Exception $e ) {
			// translators: %s = error message, not to be translated.
			$message = sprintf( __( 'There was an error while trying to get the DCR payment address: "%s".', 'decred' ), $e->getMessage() );
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * Call Decred API to get a new DCR receiving address by using
	 * the master public key saved in settings.
	 *
	 * @param int $index counter for the extended key.
	 *
	 * @return string
	 */
	public function get_new_payment_address( $index ) {

		$mpk = $this->settings['master_public_key'];

		$extended_key = ExtendedKey::fromString( $mpk );

		return $extended_key
			->publicChildKey( 0 )
			->publicChildKey( $index )
			->getAddress();
	}

	/**
	 * Once the order has been created, save to the DB additional
	 * payment fields for future use/reference.
	 *
	 * They will show in the order's admin page as "custom fields".
	 *
	 * We used the session to save them temporarily at different moments,
	 * we now recover them all from the session.
	 *
	 * WC peculiarities:
	 * - orders are saved as "posts"
	 * - additional "custom fields" are saved as "post metadata"
	 *
	 * @param int $order_id .
	 */
	public function woocommerce_new_order( $order_id ) {

		$fields = [ 'decred_amount', 'decred_refund_address', 'decred_payment_address' ];

		foreach ( $fields as $field ) {
			$value = WC()->session->get( $field );
			add_post_meta( $order_id, $field, $value );
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
			$order->update_status( 'on-hold', __( 'Awaiting Decred payment', 'decred' ) );
		} else {
			$order->payment_complete();
		}

		wc_reduce_stock_levels( $order_id );

		WC()->cart->empty_cart();

		// Return thankyou page redirect.
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
