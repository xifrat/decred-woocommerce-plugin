<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Checkout_Addresses extends Gateway_TestCase {

	public function test_checkout_form() {

		$g = $this->gateway;
		$g->fake_set_currency( 'USD' );
		$g->fake_set_cart_amount( 123.456 );

		$g->settings['show_refund_address'] = 'no';
		$html                               = $this->get_html( 'payment_fields' );
		$this->assertRegExp( '/.* class="decred-label".*/', $html );
		$this->assertRegExp( '/.*Amount to pay.*/', $html );
		$this->assertNotRegExp( '/.* id="wc-decred-crypto-form".*/', $html );
		$this->assertNotRegExp( '/.*Decred address for refunds.*/', $html );
		$this->assertNotRegExp( '/.*<input id="decred-refund-address".*/', $html );
		$this->assertNotRegExp( '/.* validate-required .*/', $html );
		$this->assertNotRegExp( '/.* class="required".*/', $html );

		$g->settings['show_refund_address']     = 'yes';
		$g->settings['refund_address_optional'] = 'no';
		$html                                   = $this->get_html( 'payment_fields' );
		$this->assertRegExp( '/.* class="decred-label".*/', $html );
		$this->assertRegExp( '/.* id="wc-decred-crypto-form".*/', $html );
		$this->assertRegExp( '/.*Decred address for refunds.*/', $html );
		$this->assertRegExp( '/.*<input id="decred-refund-address".*/', $html );
		$this->assertRegExp( '/.*validate-required .*/', $html );
		$this->assertRegExp( '/.* class="required".*/', $html );

		$g->settings['refund_address_optional'] = 'yes';
		$html                                   = $this->get_html( 'payment_fields' );
		$this->assertNotRegExp( '/.* validate-required .*/', $html );
		$this->assertNotRegExp( '/.* class="required".*/', $html );
	}

	public function test_validate_address() {
		$g = $this->gateway;

		$tests = [
			[ null, false ],
			[ true, false ],
			[ 987.123, false ],
			[ '', false ],
			[ 'aaabbbccc', false ],
			[ 'DsrPwFMQW8v4FpHn2BBWiyVm7wr8HUgTcuc', true ],
		];

		foreach ( $tests as $test ) {
			$address = $test[0];
			$result  = $test[1];
			$this->assertEquals( $g->validate_address( $address ), $result, "Failing test address: $address" );
		}
	}

	public function test_validate_refund_address_field() {

		$g = $this->gateway;

		// refund address not shown: address should be empty.
		$g->settings['show_refund_address'] = 'no'; // note settings property is implementation dependant.
		$this->refund_addr_ok( '' );
		$this->refund_addr_wrong( 'fake1FAKE1fake', 'plugin error' );
		$this->refund_addr_wrong( 'Tsv9xcExyjJTFELNhhnp19xNF1jhgUfL1kG', 'plugin error' );

		// refund address shown and required.
		$g->settings['show_refund_address']     = 'yes';
		$g->settings['refund_address_optional'] = 'no';
		$this->refund_addr_wrong( '' );
		$this->refund_addr_wrong( 'fake2FAKE2fake' );
		$this->refund_addr_ok( 'Dso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );

		// refund address shown but optional.
		$g->settings['refund_address_optional'] = 'yes';
		$this->refund_addr_ok( '' );
		$this->refund_addr_wrong( 'fake3FAKE3fake' );
		$this->refund_addr_ok( 'Tso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );

		// verify validation of two initial characters (Ds mainnet, Ts testnet).
		$this->refund_addr_wrong( 'Wso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );
		$this->refund_addr_wrong( 'Tzo2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );
	}

	private function refund_addr_ok( $address ) {

		$notices = $this->validate_refund_addr( $address );

		if ( isset( $notices['error'] ) ) {
			$error_message = array_pop( $notices['error'] );
		} else {
			$error_message = '';
		}

		$this->assertTrue( empty( $error_message ), "Failed TRUE address: $address with error '$error_message'" );

		if ( ! empty( $address ) ) {
			$this->assertEquals( WC()->session->get( 'decred_refund_address' ), $address );
		}
	}

	private function refund_addr_wrong( $address, $notice_portion = 'enter a valid' ) {

		$notices = $this->validate_refund_addr( $address );

		$this->assertArrayHasKey( 'error', $notices );
		$this->assertTrue( count( $notices['error'] ) > 0 );
		$last_notice = array_pop( $notices['error'] );
		$this->assertContains( $notice_portion, $last_notice );
		$this->assertEmpty( WC()->session->get( 'decred_refund_address' ) );
	}

	private function validate_refund_addr( $address ) {
		WC()->session->set( 'wc_notices', null );
		$_POST['decred-refund-address'] = $address;
		$this->gateway->validate_refund_address_field();
		return WC()->session->get( 'wc_notices', [] );
	}

	public function test_get_payment_address() {

		$this->payment_addr_wrong( null );
		$this->payment_addr_wrong( 'aaa' );
		$this->payment_addr_wrong( -123456 );
		$this->payment_addr_wrong( 2 ** 31 );
		$this->payment_addr_wrong( PHP_INT_MAX );
		$this->payment_addr_wrong( rand( 2 ** 31, PHP_INT_MAX ) );
		$this->payment_addr_wrong( rand( -1, -PHP_INT_MAX ) );

		$this->payment_addr_ok( 0, 'TsnhNSGggWVzrLu6nnrcUBFJ8U24aAuH5Av' );
		$this->payment_addr_ok( 222, 'Tsn3sUmXNPJhGYnVc2pbHRr73e85NQQszBM' );
		$this->payment_addr_ok( 333, 'TsaDsANo9v8rekPZ9vehVKDUsja1gMhBs1g' );
		$this->payment_addr_ok( 55555, 'Tsb6hPdLiyTKNjwyVss1mjrTGCzu3iE9JCh' );
		$this->payment_addr_ok( 2 ** 31 - 1, 'TsSAi7gMrMqHnDcAfb4kx6Z7KAepnUApqq8' );
	}

	private function payment_addr_ok( $index, $address = null ) {
		$result = $error = '';
		try {
			$result = $this->gateway->get_api_payment_address( self::TEST_MPK, $index );
		} catch ( \Exception $e ) {
			$error = $e->getMessage();
		}
		$this->assertEquals( $address, $result, "index: $index addresses: $address -- $result error: $error" );
	}

	private function payment_addr_wrong( $index ) {
		$address = $error = '';
		try {
			$address = $this->gateway->get_api_payment_address( self::TEST_MPK, $index );
		} catch ( \Exception $e ) {
			$error = $e->getMessage();
		}
		$this->assertContains( 'index should be', $error, "index: $index address: $address error: $error" );
	}

}
