<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

require_once dirname( __DIR__ ) . '/includes/class-constant.php';
use Decred\Payments\WooCommerce\Constant;

class GW_Checkout_Order extends Gateway_TestCase {

	public function test_wc_new_order() {
		
		$order_id = 2**31 -1;
		$this->gateway->settings['master_public_key'] = self::MPK;
		WC()->session->set( 'decred_amount', 333.7777777 );
		WC()->session->set( 'decred_refund_address', 'REFUND ADDRESS' );
		
		$this->gateway->wc_new_order( $order_id );
		
		$all = get_post_meta( $order_id );
		
		$this->assertEquals( $all['decred_amount'][0], 333.7777777 );
		$this->assertEquals( $all['decred_refund_address'][0], 'REFUND ADDRESS' );
		$this->assertEquals( $all['decred_payment_address'][0], 'TsSAi7gMrMqHnDcAfb4kx6Z7KAepnUApqq8' );
		
		// scheduled event
		
		$scheduled_events = _get_cron_array();
		$this->assertNotEmpty( $scheduled_events );
		
		$details = [];
		foreach ( $scheduled_events as $event ) {
			if ( array_key_exists( 'decred_order_status_updater', $event ) ) {
				$details = array_shift( $event[ 'decred_order_status_updater' ] );
				break;
			}
		}
		$this->assertNotEmpty( $details );
		
		$this->assertArrayHasKey( 'schedule', $details );
		$this->assertEquals( $details[ 'schedule' ], 'decred_schedule' );
		
		$this->assertArrayHasKey( 'interval', $details );
		$interval = $details[ 'interval' ];
		$this->assertEquals( $interval, Constant::CRON_INTERVAL );
	}

}