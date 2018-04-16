<?php
/**
 * Order status updater, (eventually) called via WP-cron.
 */

namespace Decred\Payments\WooCommerce;

use Decred\Data\Transaction;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 * Decred Payments
 *
 * Order status updater.
 *
 * At regular intervals (see constant), while there's some unpaid Decred order.
 *
 * @class       Decred\Payments\WooCommerce\StatusUpdater
 * @version     0.1
 * @author      xifrat
 */
class Status_Updater {

	/**
	 * Saved plugin settings. See GW_Base->form_fieds.
	 *
	 * @var array settings
	 */
	private $settings;

	/**
	 * Run the order status updater.
	 */
	public function execute() {
		$this->settings = get_option( 'woocommerce_decred_settings', null );

		$args                = [];
		$args['post_type']   = 'shop_order';
		$args['post_status'] = [ 'wc-pending', 'wc-on-hold' ];
		$args['meta_query']  = [
			[
				'key'   => '_payment_method',
				'value' => 'decred',
			],
		];
		$args['orderby']     = 'ID';
		$args['order']       = 'ASC';

		$query = new \WP_Query( $args );
		// @codingStandardsIgnoreLine
		// echo "\nSTATUS UPDATER QUERY: $query->request\n\nFound $query->found_posts orders\n"; // TODO convert to debug log message.
		while ( $query->have_posts() ) {
			$query->the_post();
			$order = wc_get_order( $query->post->ID );
			$this->process_order( $order );
		}

		wp_reset_postdata();
	}

	// @codingStandardsIgnoreLine
	private function process_order( \WC_Order $order ) {

		$order_id               = $order->get_id();
		$decred_payment_address = get_post_meta( $order_id, 'decred_payment_address', true );

		$order_created_timestamp = $order->get_date_created()->getTimestamp();
		$order_created_datetime  = new \DateTime( '@' . $order_created_timestamp );

		$mpk = $this->settings['master_public_key'];
		$api = new API_Helper( $mpk );

		$transactions = $api->get_transactions( $decred_payment_address, $order_created_datetime );

		if ( $transactions ) {
			foreach ( $transactions as $transaction ) {
				$this->process_transaction( $order, $transaction, $decred_payment_address );
			}
		}
	}

	// @codingStandardsIgnoreLine
	private function process_transaction( \WC_Order $order, Transaction $transaction, $decred_payment_address ) {

		// echo "\n ORDER " . $order->get_id(); 	// @codingStandardsIgnoreLine
		switch ( $order->get_status() ) {
			case 'pending':
				$this->process_pending_order( $order, $transaction, $decred_payment_address );
				break;
			case 'on-hold':
				$this->process_on_hold_order( $order, $transaction, $decred_payment_address );
				break;
			default:
				// This shouldn't happen, behaviour unclear so do nothing. TODO throw exception and/or log.
		}
	}

	// @codingStandardsIgnoreLine
	private function process_pending_order( \WC_Order $order, Transaction $transaction, $decred_payment_address ) {

		$order_id         = $order->get_id();
		$trans_out_amount = $transaction->getOutAmount( $decred_payment_address );
		$order_amount     = get_post_meta( $order_id, 'decred_amount', true );

		if ( $trans_out_amount < $order_amount ) {
			// this shouldn't happen, TODO something, at least throw exception and/or log.
			return;
		}

		$trans_txid     = $transaction->getTxid();
		$trans_confirms = $transaction->getConfirmations();

		add_post_meta( $order_id, 'txid', $trans_txid );
		add_post_meta( $order_id, 'confirmations', $trans_confirms );

		$this->update_status( $order, $trans_confirms );

		if ( $trans_out_amount > $order_amount ) {
			// TODO something, maybe sent email to merchant, at least log.
			// Might depend on how much more is received. Probably an order note would do.
		}
	}

	// @codingStandardsIgnoreLine
	private function process_on_hold_order( \WC_Order $order, Transaction $transaction ) {

		$order_id   = $order->get_id();
		$trans_txid = $transaction->getTxid();
		$order_txid = get_post_meta( $order_id, 'txid', true );

		if ( $trans_txid != $order_txid ) {
			// echo " TXID DIFER "; // TODO something, at least throw exception and/or log. 	// @codingStandardsIgnoreLine
			return;
		}

		$trans_confirms = $transaction->getConfirmations();
		$order_confirms = get_post_meta( $order_id, 'confirmations', true );

		if ( $trans_confirms < $order_confirms ) {
			// echo " TX CONFIRMS LOW "; // this shouldn't happen, TODO something, at least throw exception and/or log. // @codingStandardsIgnoreLine
			return;
		}

		if ( $trans_confirms == $order_confirms ) {
			// echo " NO NEW CONFIRMS "; // TODO log. // @codingStandardsIgnoreLine
			return; // no new confirmations, nothing to do.
		}

		// new confirmations.
		update_post_meta( $order_id, 'confirmations', $trans_confirms );

		$this->update_status( $order, $trans_confirms );
	}

	// @codingStandardsIgnoreLine
	private function update_status( \WC_Order $order, int $trans_confirms ) {

		$current_status        = $order->get_status();
		$confirmations_to_wait = $this->settings['confirmations_to_wait'];

		// echo " CFW $confirmations_to_wait TRCF $trans_confirms\n"; // @codingStandardsIgnoreLine
		if ( $trans_confirms >= $confirmations_to_wait ) {
			$new_status = 'processing';
		} else {
			$new_status = 'on-hold';
		}

		if ( $new_status == $current_status ) {
			return; // no change, nothing to update.
		}

		// status changed, update order.
		$order->set_status( $new_status );
		$order->save();
	}

}
