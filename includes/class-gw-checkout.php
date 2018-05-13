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
	 * @throws \Exception Possible diverse errors from decred-php-api.
	 */
	public function convert_to_dcr( $currency, $amount ) {
		$dcr_amount = \Decred\Rate\CoinMarketCap::getRate( $currency )->convertToCrypto( $amount );
		// API may return more precision but we only use 7 decimal digits.
		$dcr_amount = round( $dcr_amount, 7 );
		return $dcr_amount;
	}

	/**
	 * HTML form with payment fields
	 *
	 * TODO error management
	 */
	public function payment_fields() {

		WC()->session->set( 'decred_amount', null );

		try {

			$dcr_amount = $this->get_dcr_amount();

			// save amount now to retrieve it later when order created.
			WC()->session->set( 'decred_amount', $dcr_amount );

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
	 * @throws \Exception Errors in currency or total cart amount.
	 * @throws \Exception Errors from decred-php-api.
	 *
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
			$address_is_valid = $this->validate_address( $address );
		}

		if ( $address_is_valid ) {
			if ( ! empty( $address ) ) {
				// save address now to retrieve it later when order created.
				WC()->session->set( 'decred_refund_address', $address );
			}
		} else {
			// translators: parameter is address entered by user, not to be translated.
			$message = __( 'Please enter a valid Decred address for refunds. Wrong address "%s"', 'decred' );
			wc_add_notice( sprintf( $message, $address ), 'error' );
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
	 * Get a DCR receiving address specific for this order.
	 *
	 * We use:
	 * - the wallet's master public key saved in settings.
	 * - the order id as index for the child key.
	 *
	 * With these we use decred-php-api to get the corresponding address.
	 * Note the order id could potentially be out of the allowed range.
	 * In case of errors, we save a short error message in the address field itself. TODO nicer.
	 *
	 * @param int $order_id .
	 * @return string DCR receiving address or error message.
	 */
	public function get_payment_address( $order_id ) {

		try {
			$mpk     = $this->settings['master_public_key'];
			$address = $this->get_api_payment_address( $mpk, $order_id );

		} catch ( \Exception $e ) { // TODO log.
			$address = __( 'ERROR GETTING DECRED ADDRESS', 'decred' );
		}

		return $address;
	}

	/**
	 * Call Decred API to get a DCR receiving address.
	 *
	 * @param string $mpk master public key. TODO validate or guarantee it's correct. Or use a cached ExtendedKey object.
	 * @param int    $index for the child key.
	 * @return string The corresponding address for the child index of the default account 0.
	 * @throws \Exception If $index not a positive number less tham 2^31.
	 * @throws \Exception Errors from decred-php-api.
	 */
	public function get_api_payment_address( $mpk, $index ) {

		if ( ! is_numeric( $index ) || $index < 0 || $index >= 2 ** 31 ) { // TODO this check probably better moved to decred-php-api.
			throw new \Exception(
				'BIP32 child address index should be a positive number less tham 2^31.'
				. 'Received value "' . $index . '"'
			);
		}

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
	public function wc_new_order( $order_id ) {

		add_post_meta( $order_id, 'decred_amount', WC()->session->get( 'decred_amount' ) );

		add_post_meta( $order_id, 'decred_refund_address', WC()->session->get( 'decred_refund_address' ) );

		add_post_meta( $order_id, 'decred_payment_address', $this->get_payment_address( $order_id ) );

		if ( ! wp_next_scheduled( 'decred_order_status_updater' ) ) {
			wp_schedule_event( time(), 'decred_schedule', 'decred_order_status_updater' );
		}

	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id .
	 * @return array
	 */
	public function process_payment( $order_id ) {
		// TODO TEST (may be difficult).
		$order = wc_get_order( $order_id );

		if ( $order->get_total() == 0 ) {
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
		if ( $this->instructions && ! $sent_to_admin && 'decred' === $order->get_payment_method() && $order->has_status( 'pending' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

}
