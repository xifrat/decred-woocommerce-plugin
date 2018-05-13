<?php
/**
 * Payment Gateway methods related to the "thankyou" page that
 * shows after user proceeds with payment in the checkout page.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\GW_Thankyou
 * @extends     GW_Checkout
 * @version     0.1
 * @author      xifrat
 */
class GW_Thankyou extends GW_Checkout {

	/**
	 * Add a note to the "order received" text on top of the thankyou page
	 *
	 * @param string $text default text WooCommerce shows.
	 */
	function thankyou_order_received_text( $text ) {
		// use .= here if you want to keep the default (not much useful) message.
		$text = __( 'PLEASE SEE INSTRUCCIONS BELOW FOR PAYMENT WITH DECRED.', 'decred' );
		return $text;
	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id .
	 */
	public function thankyou_page( $order_id ) {

		/** @var \WC_Order $order */
		$order = wc_get_order( $order_id );

		$dcr_order_status    = $order->get_status();
		$dcr_payment_address = get_post_meta( $order_id, 'decred_payment_address', true );
		$dcr_amount          = get_post_meta( $order_id, 'decred_amount', true );
		$dcr_txid            = get_post_meta( $order_id, 'decred_txid', true );

		$dcr_code = sprintf(
			'decred:%s?%s', $dcr_payment_address, http_build_query(
				[ 'amount' => $dcr_amount ]
			)
		);

		require __DIR__ . '/html-thankyou.php';
	}
}
