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
	 * Run the order status updater. TODO TEST complete coverage with all execution paths and error conditions.
	 */
	public function execute() {

		$this->log( 'StatusUpdater->execute() BEGIN.' );
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
		//$this->log( "Query: $query->request" );

		if ( 0 == $query->found_posts ) {
			// TODO unschedule.
			$this->log( 'No orders waiting for DCR payment.' );
			return;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$this->log( 'Processing order ' . $query->post->ID );
			$order = wc_get_order( $query->post->ID );
			$this->process_order( $order );
		}

		wp_reset_postdata();
	}

	// @codingStandardsIgnoreLine
	private function process_order( \WC_Order $order ) {

		$order_id = $order->get_id();

		/*
		 * Obtain transaction data.
		 */
		$mpk = $this->settings['master_public_key'];
		$api = new API_Helper( $mpk );

		$decred_payment_address = get_post_meta( $order_id, 'decred_payment_address', true );

		$order_created_timestamp = $order->get_date_created()->getTimestamp();
		$order_created_datetime  = new \DateTime( '@' . $order_created_timestamp );

		$transactions = $api->get_transactions( $decred_payment_address, $order_created_datetime );

		// No transactions found for that address yet, skip this order.
		if ( empty( $transactions ) ) {
			$this->log( 'No transactions found' );
			return;
		}

		if ( count( $transactions ) != 1 ) {
			// TODO send notice to merchant & maybe customer, maybe throw exception.
			$this->log( "ERROR: only one transaction per order supported, order id $order_id" );
			return;
		}

		$transaction = $transactions[0];

		/*
		 * Transaction ID: save or verify if already saved
		 */
		$trans_txid = $transaction->getTxid();
		$order_txid = get_post_meta( $order_id, 'decred_txid', true );

		if ( empty( $order_txid ) ) {
			update_post_meta( $order_id, 'decred_txid', $trans_txid );
		} elseif ( $trans_txid != $order_txid ) {
			// TODO maybe throw exception.
			$this->log( "INTERNAL ERROR: Transaction ID $trans_txid differs from saved previously $order_txid for order id $order_id" );
			return;
		}

		/*
		 * Confirmations: it there are new ones update custom field & continue, otherwise stop here.
		 */
		$trans_confirms = $transaction->getConfirmations();
		$order_confirms = get_post_meta( $order_id, 'decred_confirmations', true );
		if ( empty( $order_confirms ) ) {
			$order_confirms = 0;
		}
		if ( $trans_confirms == $order_confirms ) {
			$this->log( 'No new confirmations, nothing to do.' );
			return;
		}
		if ( $trans_confirms < $order_confirms ) {
			// TODO maybe throw exception.
			$this->log( 'INTERNAL ERROR: less confirmations than before!.' );
			return;
		}
		update_post_meta( $order_id, 'decred_confirmations', $trans_confirms );
		$this->log( "Confirmations updated, before: $order_confirms, after: $trans_confirms." );

		/*
		 * Verify amounts
		 */
		$trans_out_amount = $transaction->getOutAmount( $decred_payment_address );
		$order_amount     = get_post_meta( $order_id, 'decred_amount', true );

		if ( $trans_out_amount < $order_amount ) {
			// TODO send notice to merchant, maybe throw exception.
			$this->log( "Payment failed: Transaction amount $trans_out_amount less than order amount $order_amount for order id $order_id" );
			$order->update_status( 'pending', __( 'Received LESS DCR THAN EXPECTED.', 'decred' ) );
			$order->add_order_note( __( 'Order status changed to pending because we received less DCR than expected.', 'decred' ), true );
			return;
		}

		/*
		 * Update status according to the number of new confirmations
		 */
		$current_status        = $order->get_status();
		$confirmations_to_wait = $this->settings['confirmations_to_wait'];

		if ( $trans_confirms >= $confirmations_to_wait ) {
			$this->log( "More than $confirmations_to_wait confirmations, will change status" );
			$message = __( 'DCR payment confirmed.', 'decred' );
			if ( $trans_out_amount > $order_amount ) {
				// TODO send notice to merchant & maybe customer.
				$this->log( "Transaction amount $trans_out_amount more than order amount $order_amount for order id $order_id" );
				$message .= ' ' . __( 'Note that you received MORE DCR THAN EXPECTED.', 'decred' );
			}
			$new_status = 'processing';
		} else {
			$this->log( "Less than $confirmations_to_wait confirmations, no enough yet." );
			// should be already on-hold but we allow for others also.
			// note possible statuses here depend on the query filters used.
			$new_status = 'on-hold';
			$message    = __( 'Awaiting DCR payment.', 'decred' );
		}

		if ( $new_status == $current_status ) {
			$this->log( 'No change, nothing to update.' );
			return;
		}

		$order->update_status( $new_status, $message );
		$this->log( "status changed from $current_status to $new_status." );
	}

	// @codingStandardsIgnoreLine
	private function log( $msg ) {
		// TODO generic & better.
		global $decred_wc_plugin;
		$decred_wc_plugin->tmp_log( $msg );
	}
}
