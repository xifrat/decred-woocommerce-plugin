<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Checkout_Order extends Gateway_TestCase {

	public function test_woocommerce_new_order() {
		
		$order_id = 2**31 -1;
		$this->gateway->settings['master_public_key'] = self::MPK;
		WC()->session->set( 'decred_amount', 333.7777777 );
		WC()->session->set( 'decred_refund_address', 'REFUND ADDRESS' );
		
		$this->gateway->woocommerce_new_order( $order_id );
		
		$all = get_post_meta( $order_id );
		
		$this->assertEquals( $all['decred_amount'][0], 333.7777777 );
		$this->assertEquals( $all['decred_refund_address'][0], 'REFUND ADDRESS' );
		$this->assertEquals( $all['decred_payment_address'][0], 'TsSAi7gMrMqHnDcAfb4kx6Z7KAepnUApqq8' );
	}

}