<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Checkout_Order extends Gateway_TestCase {

	public function test_wc_new_order() {

		$order_id                                     = 2 ** 31 - 1;
		$this->gateway->settings['master_public_key'] = self::TEST_MPK;
		WC()->session->set( 'decred_amount', 333.7777777 );
		WC()->session->set( 'decred_refund_address', 'REFUND ADDRESS' );

		$this->gateway->wc_new_order( $order_id );

		// expected custom fields set and no duplicates
		$fields = [ 
			'decred_amount' => 333.7777777, 
			'decred_refund_address' => 'REFUND ADDRESS',
			'decred_payment_address' => 'TsSAi7gMrMqHnDcAfb4kx6Z7KAepnUApqq8'
		];
		$this->verify_post_meta( $order_id, $fields );

		// scheduled event.
		$scheduled_events = _get_cron_array();
		$this->assertNotEmpty( $scheduled_events );

		$details = [];
		foreach ( $scheduled_events as $event ) {
			if ( array_key_exists( 'decred_order_status_updater', $event ) ) {
				$details = array_shift( $event['decred_order_status_updater'] );
				break;
			}
		}
		$this->assertNotEmpty( $details );

		$this->assertArrayHasKey( 'schedule', $details );
		$this->assertEquals( $details['schedule'], 'decred_schedule' );

		$this->assertArrayHasKey( 'interval', $details );
		$interval = $details['interval'];
		$this->assertEquals( $interval, 60 );
	}

}
