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
	 */
	public function thankyou_page() {

		$this->get_dcr_data_from_order();

		require __DIR__ . '/html-thankyou.php';
	}

	/**
	 * Recover DCR amount & address recorded at checkout time
	 */
	public function get_dcr_data_from_order() {

		// TODO replace by amount saved at checkout.
		$this->dcr_amount = 2.2223333;

		// TODO replace by receiving address from wallet saved at checkout.
		$this->dcr_payment_address = 'TsWjioPrP8E1TuTMmTrVMM2BA4iPrjQXBpR';
	}

}
