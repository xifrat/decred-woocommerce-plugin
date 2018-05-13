<?php

namespace Decred\Payments\WooCommerce\Test;

class Dummy_Gateway extends \WC_Payment_Gateway {
	public function __construct( $id ) {
		$this->id = $id;
	}
}

abstract class Base_TestCase extends \WP_UnitTestCase {

	const TEST_MPK = 'tpubVooPRcVnuzBcqypmxFvcU2wfuHDrdq9QB6xHCgwPkGQssALSA2j96HG41EYDzuj1a1taYqMiiMTCF8L4ExtZ1199rNJMdeMcFyPziLf4LmK';

	const MAIN_MPK = 'dpubZFH1kErmwA1ZoDJcqLJ5KHH5kFtBUDVPHFUMQpLaMYCmdWszRFY4nMaZYmn3VK7ewndHrC8D5Hx1pfWSq6jQuMq8NEzmueWMBxqnYUXMgEh';

	public function actions_testcase( $actions, $object, $callbacks_depth = 1 ) {

		global $wp_filter;

		foreach ( $actions as $action ) {
			// actions get saved in $wp_filter as WP_Hook objects.
			$this->assertArrayHasKey( $action, $wp_filter );
			$this->assertEquals( 'WP_Hook', get_class( $wp_filter[ $action ] ) );
			// verify hook's class & method.
			$arr = array_shift( $wp_filter[ $action ]->callbacks );
			// echo "*** "; print_r(array_keys($arr));
			// by default we check the last callback.
			// in special cases there may be same-name filters added on top so we skip them.
			for ( $i = 1; $i <= $callbacks_depth; $i++ ) {
				$arr2 = array_pop( $arr );
			}
			$arr3 = array_shift( $arr2 );
			$this->assertEquals( get_class( $object ), get_class( $arr3[0] ) );
			$this->assertTrue(
				method_exists( $arr3[0], $arr3[1] ),
				'Missing method ' . $arr3[1]
			);
		}
	}
	
	protected function verify_post_meta( $order_id, $fields ) {
		
		foreach( $fields as $field => $value ) {
			$saved_values = get_post_meta( $order_id, $field );
			$count = count($saved_values);
			$this->assertTrue( $count == 1, "custom field $field has $count values, shoud be 1." );
			$this->assertEquals( $value, $saved_values[0],  "custom field $field has value '$saved_values[0]' instead of '$value'.");
		}
	}

	private $product;
	
	protected function create_order( $data ) {
		
		if ( empty( $this->product ) ) {
			$this->product = $this->create_product(); // id 3
		}
		
		$order = wc_create_order( $data );
		$order->add_product( $this->product, 3 );
		
		// create a single sample product, it will probably use post id #3
		// #1 & #2 possibly reserved for minimal default pages.


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
			update_post_meta( $order_id, 'decred_amount', $data['decred_amount'] );
			update_post_meta( $order_id, 'decred_payment_address', $data['decred_payment_address'] );
		}
		if ( isset( $data['decred_txid'] ) ) {
			add_post_meta( $order_id, 'decred_txid', $data['decred_txid'] );
		}
		
		return $order;
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
