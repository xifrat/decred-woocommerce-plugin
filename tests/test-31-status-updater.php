<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-base-testcase.php';

class StatusUpdater extends Base_TestCase {

	public function test_updater() {

		global $decred_wc_plugin;

		if ( ! extension_loaded( 'timecop' ) ) {
			echo "\n " . __METHOD__ . ': SKIPPED: php-timecop extension required for testing';
			return;
		}

		$curl = 'curl --silent --insecure https://testnet.dcrdata.org/api/address/TsmwpNgqpdEwZxb4brv4Nm2VUnysMcmrLtU'
			. ' | tr -d \'"\''
			. ' | sed \'s/^.*confirmations:\([0-9]*\).*$/\1/\'';

		$current_confirms = (int) `$curl`;

		$settings = [ 'master_public_key' => self::TEST_MPK ];
		add_option( 'woocommerce_decred_settings', $settings );

		// the sample transactions we check where created on 2018-04-14 or later
		// we force the orders to have that date so the blockchain search finds them
		timecop_travel( mktime( 0, 0, 0, 4, 14, 2018 ) );

		/*
		 * LOOP ONE: orders 4-5
		 */

		// TODO MAINNET
		// add_post_meta( $order->get_id(), 'decred_amount', 0.3 );
		// add_post_meta( $order->get_id(), 'decred_payment_address', 'Dsj8yaGaJzuchLTuuudqsQ37uxiYCKc99r5' );
		
		
		/*
		 * order #4 will be skipped, payment method cheque.
		 */
		$data = [
			'customer_id'    => 1,
			'status'         => 'wc-pending',
			'payment_method' => 'cheque',
		];
		$order = $this->create_order( $data );

		$this->assertEquals( $order->get_id(), 4 );
		
		/*
		 * order #5 with status pending should be changed to on-hold
		 */
		// rest of orders Decred payment method.
		$data['payment_method'] = 'decred';

		// there is a real testnet transaction, this address is child of self::TEST_MPK
		$data['decred_amount']          = 2;
		$data['decred_payment_address'] = 'TsmwpNgqpdEwZxb4brv4Nm2VUnysMcmrLtU';

		// still need to wait a bit more
		$settings['confirmations_to_wait'] = $current_confirms + rand( 1, 3 );
		update_option( 'woocommerce_decred_settings', $settings );

		$this->create_order( $data );

		// RUN UPDATER - 1
		$decred_wc_plugin->order_status_updater();

		// order 4 cheque no change
		$order = wc_get_order( 4 );
		$this->assertEquals( $order->get_status(), 'pending' ); // no change

		// order 5 pending changed to on-hold
		$order = wc_get_order( 5 );
		$this->assertEquals( $order->get_status(), 'on-hold' );

		// TODO review this test
		// $confirmations = get_post_meta( 5, 'decred_confirmations', true );
		// $this->assertEquals( $confirmations, $current_confirms );
		/*
		 * LOOP TWO: orders 4-5-6-7
		 */

		// order #6 with status on-hold should be changed to processing
		$data['status'] = 'wc-on-hold';
		$this->create_order( $data );

		// order #7 with status processing should be skipped
		$data['status'] = 'wc-processing';
		$this->create_order( $data );

		// order #8 with higher amount should fail
		$data['decred_amount'] = 3;
		$data['status']        = 'wc-on-hold';
		$this->create_order( $data );

		// order #9 with lower amount should pass
		$data['decred_amount'] = 1.5;
		$this->create_order( $data );

		// verify # of "posts" created (1 product + 4 orders)
		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts" );
		$this->assertEquals( $count, 7 );

		// no more waiting, now orders should get confirmed
		$settings['confirmations_to_wait'] = $current_confirms - rand( 0, 2 );
		update_option( 'woocommerce_decred_settings', $settings );

		// SIMULATE ORDER 5 GETS CONFIRMED by reducing saved confirmations
		update_post_meta( 5, 'decred_confirmations', $settings['confirmations_to_wait'] - rand( 1, 3 ) );

		/*
		 * TODO TEST WP-Cron integration. Ideally schedule updater like GW_Checkout->wc_new_order() does.
		 * Seems complex to setup. Half-way: test by HTTP GET path/to/wordpress/wp-cron.php
		 *
		 * First run above can be direct, so it will test the update process itself and this second run would
		 * test integration with WP-Cron.
		 */
		// RUN UPDATER - 2
		$decred_wc_plugin->order_status_updater();

		// order 5 pending changed to processing & confirmations updated
		$order = wc_get_order( 5 );
		$this->assertEquals( $order->get_status(), 'processing' );
		$confirmations = get_post_meta( 5, 'decred_confirmations', true );
		$this->assertEquals( $confirmations, $current_confirms );
		
		// order 6 on-hold --> processing
		$order = wc_get_order( 6 );
		$this->assertEquals( $order->get_status(), 'processing' );

		$fields = [
			'decred_confirmations' => $current_confirms,
			'decred_txid' => '899da82798f05e8ee6d28ee83b1f12932558263fd736993d2b165b3b842a47ca'
		];
		$this->verify_post_meta( 6, $fields );

		// order 7 processing, no change
		$order = wc_get_order( 7 );
		$this->assertEquals( $order->get_status(), 'processing' );

		// order 8 on-hold --> pending (insufficent amount)
		$order = wc_get_order( 8 );
		$this->assertEquals( $order->get_status(), 'pending' );

		// order 9 on-hold --> processing (higher amount accepted) // TODO check warning
		$order = wc_get_order( 9 );
		$this->assertEquals( $order->get_status(), 'processing' );

		timecop_return();
	}



}
