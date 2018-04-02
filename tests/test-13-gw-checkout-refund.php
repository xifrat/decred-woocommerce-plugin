<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Checkout_Refund extends Gateway_TestCase {

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

	public function test_validate_fields() {

		$g = $this->gateway;

		// refund address not shown: address should be empty.
		$g->settings['show_refund_address'] = 'no'; // note settings property is implementation dependant.
		$this->validate_true( '' );
		$this->validate_false( 'fake1FAKE1fake', 'plugin error' );
		$this->validate_false( 'Tsv9xcExyjJTFELNhhnp19xNF1jhgUfL1kG', 'plugin error' );

		// refund address shown and required.
		$g->settings['show_refund_address']     = 'yes';
		$g->settings['refund_address_optional'] = 'no';
		$this->validate_false( '' );
		$this->validate_false( 'fake2FAKE2fake' );
		$this->validate_true( 'Dso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );

		// refund address shown but optional.
		$g->settings['refund_address_optional'] = 'yes';
		$this->validate_true( '' );
		$this->validate_false( 'fake3FAKE3fake' );
		$this->validate_true( 'Tso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );

		// verify validation of two initial characters (Ds mainnet, Ts testnet).
		$this->validate_false( 'Wso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );
		$this->validate_false( 'Tzo2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf' );

	}

	private function validate_true( $address ) {

		$_POST['decred-refund-address'] = $address;
		$this->assertTrue( $this->gateway->validate_fields(), "Failed TRUE address: $address" );

		if ( ! empty( $address ) ) {
			$this->assertEquals( WC()->session->get( 'decred_refund_address' ), $address );
		}
	}

	private function validate_false( $address, $notice_portion = 'enter a valid' ) {

		$_POST['decred-refund-address'] = $address;
		$this->assertFalse( $this->gateway->validate_fields(), "Failed FALSE address: $address" );

		$notices = WC()->session->get( 'wc_notices', array() );
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertTrue( count( $notices['error'] ) > 0 );
		$last_notice = array_pop( $notices['error'] );
		$this->assertContains( $notice_portion, $last_notice );
		$this->assertEmpty( WC()->session->get( 'decred_refund_address' ) );
	}

	public function test_validate_refund_address() {

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
			$this->assertEquals( $g->validate_refund_address( $address ), $result, "Failing test address: $address" );
		}

	}

}
