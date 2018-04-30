<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-base-testcase.php';

class Dummy_Gateway extends \WC_Payment_Gateway {
	public function __construct( $id ) {
		$this->id = $id;
	}
}

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

		// create a single sample product, it will probably use post id #3
		// #1 & #2 possibly reserved for minimal default pages.
		$product = $this->create_product();

		/*
		 * LOOP ONE: orders 4-5
		 */

		// MAINNET
		// add_post_meta( $order->get_id(), 'decred_amount', 0.3 );
		// add_post_meta( $order->get_id(), 'decred_payment_address', 'Dsj8yaGaJzuchLTuuudqsQ37uxiYCKc99r5' );
		/*
		 * order #4 will be skipped, payment method cheque.
		 */
		$data = [
			'order_id'       => 0,
			'customer_id'    => 1,
			'status'         => 'wc-pending',
			'payment_method' => 'cheque',
		];
		$this->create_order( $data, $product );

		/*
		 * order #5 with status pending should be changed to processing
		 */
		// rest of orders Decred payment method.
		$data['payment_method'] = 'decred';

		// there is a real testnet transaction, this address is child of self::TEST_MPK
		$data['decred_amount']          = 2;
		$data['decred_payment_address'] = 'TsmwpNgqpdEwZxb4brv4Nm2VUnysMcmrLtU';

		// still need to wait a bit more
		$settings['confirmations_to_wait'] = $current_confirms + rand( 1, 3 );
		update_option( 'woocommerce_decred_settings', $settings );

		$this->create_order( $data, $product );

		// RUN UPDATER - 1
		$decred_wc_plugin->order_status_updater();

		// order 4 cheque no change
		$order = wc_get_order( 4 );
		$this->assertEquals( $order->get_status(), 'pending' ); // no change

		// order 5 pending no change
		$order = wc_get_order( 5 );
		$this->assertEquals( $order->get_status(), 'pending' );

		// TODO review this test
		// $confirmations = get_post_meta( 5, 'confirmations', true );
		// $this->assertEquals( $confirmations, $current_confirms );
		/*
		 * LOOP TWO: orders 4-5-6-7
		 */

		// order #6 with status on-hold should be changed to processing
		$data['status'] = 'wc-on-hold';
		$this->create_order( $data, $product );

		// order #7 with status processing should be skipped
		$data['status'] = 'wc-processing';
		$this->create_order( $data, $product );

		// order #8 with higher amount should fail
		$data['decred_amount'] = 3;
		$data['status']        = 'wc-on-hold';
		$this->create_order( $data, $product );

		// order #9 with lower amount should pass
		$data['decred_amount'] = 1.5;
		$this->create_order( $data, $product );

		// verify # of "posts" created (1 product + 4 orders)
		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts" );
		$this->assertEquals( $count, 7 );

		// no more waiting, now orders should get confirmed
		$settings['confirmations_to_wait'] = $current_confirms - rand( 0, 2 );
		update_option( 'woocommerce_decred_settings', $settings );

		// SIMULATE ORDER 5 GETS CONFIRMED by reducing saved confirmations
		update_post_meta( 5, 'confirmations', $settings['confirmations_to_wait'] - rand( 1, 3 ) );

		// RUN UPDATER - 2
		$decred_wc_plugin->order_status_updater();

		// order 5 pending no change
		$order = wc_get_order( 5 );
		$this->assertEquals( $order->get_status(), 'pending' );

		// TODO review this test
		// $confirmations = get_post_meta( 5, 'confirmations', true );
		// $this->assertEquals( $confirmations, $current_confirms );
		
		// order 6 on-hold --> processing
		$order = wc_get_order( 6 );
		$this->assertEquals( $order->get_status(), 'processing' );

		$fields = [
			'confirmations' => $current_confirms,
			'txid' => '899da82798f05e8ee6d28ee83b1f12932558263fd736993d2b165b3b842a47ca'
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

	private function create_order( $data, $product ) {

		$order = wc_create_order( $data );
		$order->add_product( $product, 3 );
		$order->calculate_totals();

		$address = array(
			'first_name' => 'Remi',
			'last_name'  => 'Corson',
			'company'    => 'Automattic',
			'email'      => 'no@spam.com',
			'phone'      => '123-123-123',
			'address_1'  => '123 Main Woo st.',
			'address_2'  => '100',
			'city'       => 'San Francisco',
			'state'      => 'Ca',
			'postcode'   => '92121',
			'country'    => 'US',
		);
		$order->set_address( $address, 'billing' );
		$order->set_address( $address, 'shipping' );

		$payment_method = $data['payment_method'];

		/**
		 * We could use here the real gateway, it would be a more thourough test but also more complex
		 * to setup because $order->save() below would result in executing GW_Checkout->wc_new_order()
		 * and setting the post meta fields, among other actions.
		 *
		 * tentative implementation:
		 *
		 * $payment_gateways = WC()->payment_gateways->payment_gateways();
		 * $gateway = $payment_gateways[ $payment_method ];
		 * if ( $payment_method == 'decred' ) {
		 *    $gateway->settings['master_public_key'] = self::TEST_MPK;
		 * }
		 * $order->set_payment_method( $gateway );
		 */

		$order->set_payment_method( new Dummy_Gateway( $payment_method ) );

		$order->save();

		$order_id = $order->get_id();

		if ( $payment_method == 'decred' ) {
			add_post_meta( $order_id, 'decred_amount', $data['decred_amount'] );
			add_post_meta( $order_id, 'decred_payment_address', $data['decred_payment_address'] );
		}
		if ( isset( $data['txid'] ) ) {
			add_post_meta( $order_id, 'txid', $data['txid'] );
		}

	}

	private function create_product() {

		$data = array(
			'Name'        => 'Product A',
			'Description' => 'This is a product A',
			'SKU'         => '10020030A',
		);

		$user_id = get_current_user();

		$post_id = wp_insert_post(
			array(
				'post_author'  => $user_id,
				'post_title'   => $data['Name'],
				'post_content' => $data['Description'],
				'post_status'  => 'publish',
				'post_type'    => 'product',
			)
		);

		wp_set_object_terms( $post_id, 'simple', 'product_type' );
		update_post_meta( $post_id, '_visibility', 'visible' );
		update_post_meta( $post_id, '_stock_status', 'instock' );
		update_post_meta( $post_id, 'total_sales', '0' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_virtual', 'yes' );
		update_post_meta( $post_id, '_regular_price', '' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', 'no' );
		update_post_meta( $post_id, '_weight', '' );
		update_post_meta( $post_id, '_length', '' );
		update_post_meta( $post_id, '_width', '' );
		update_post_meta( $post_id, '_height', '' );
		update_post_meta( $post_id, '_sku', $data['SKU'] );
		update_post_meta( $post_id, '_product_attributes', array() );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_price', '' );
		update_post_meta( $post_id, '_sold_individually', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );
		update_post_meta( $post_id, '_backorders', 'no' );
		update_post_meta( $post_id, '_stock', '' );

		$product = wc_get_product( $post_id );
		$product->set_price( 55 );

		return $product;
	}

}
