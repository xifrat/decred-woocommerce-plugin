<?php

namespace Decred\Payments\WooCommerce\Test;

class Gateway_Refund extends \WP_UnitTestCase {
	
	public function setUp() {
		$this->gateway = new \Decred\Payments\WooCommerce\Gateway;
	}
	
	public function test_checkout_form() {

		$g = $this->gateway;
		
		$g->settings[ 'show_refund_address' ] = 'no';
		$html = $this->get_form_html();
		$this->assertRegExp( '/.* class="decred-label".*/', $html );
		$this->assertRegExp( '/.*Amount to pay.*/', $html );
		$this->assertNotRegExp( '/.* id="wc-decred-crypto-form".*/', $html );
		$this->assertNotRegExp( '/.*Decred address for refunds.*/', $html );
		$this->assertNotRegExp( '/.*<input id="decred-refund-address".*/', $html );
		$this->assertNotRegExp( '/.* validate-required .*/', $html );
		$this->assertNotRegExp( '/.* class="required".*/', $html );
		
		$g->settings[ 'show_refund_address' ] = 'yes';
		$g->settings[ 'refund_address_optional' ] = 'no';
		$html = $this->get_form_html();
		$this->assertRegExp( '/.* class="decred-label".*/', $html );
		$this->assertRegExp( '/.* id="wc-decred-crypto-form".*/', $html );
		$this->assertRegExp( '/.*Decred address for refunds.*/', $html );
		$this->assertRegExp( '/.*<input id="decred-refund-address".*/', $html );
		$this->assertRegExp( '/.*validate-required .*/', $html );
		$this->assertRegExp( '/.* class="required".*/', $html );
		
		$g->settings[ 'refund_address_optional' ] = 'yes';
		$html = $this->get_form_html();
		$this->assertNotRegExp( '/.* validate-required .*/', $html );
		$this->assertNotRegExp( '/.* class="required".*/', $html );
		
	}
	
	private function get_form_html() {
		ob_start();
		$this->gateway->payment_fields();
		$html = ob_get_contents();
		ob_end_clean();
		//echo $html;
		return $html;
	}
	
	public function test_validate_fields() {
		
		$g = $this->gateway;
		
		// refund address not shown: address should be empty
		
		$g->settings[ 'show_refund_address' ] = 'no'; // note settings property is implementation dependant.
		$this->validate_true('');
		$this->validate_false('fake1FAKE1fake', 'plugin error' ); 
		$this->validate_false('Dcv9xcExyjJTFELNhhnp19xNF1jhgUfL1kG', 'plugin error');
		
		// refund address shown and required.
		
		$g->settings[ 'show_refund_address' ] = 'yes';
		$g->settings[ 'refund_address_optional' ] = 'no';
		$this->validate_false('', 'enter a valid');
		$this->validate_false('fake2FAKE2fake', 'enter a valid');
		$this->validate_true('Dso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf');
		
		// refund address shown but optional.
		
		$g->settings[ 'refund_address_optional' ] = 'yes';
		$this->validate_true('');
		$this->validate_false('fake3FAKE3fake', 'enter a valid');
		$this->validate_true('Dso2YsjNZzaDNKnomERu5sPmkcZEL2VJCPf');
		
	}
	
	private function validate_true( $address ) {
		$_POST[ 'decred-refund-address' ] = $address;
		$this->assertTrue( $this->gateway->validate_fields(), "Failed TRUE address: $address" );
	}
	
	private function validate_false( $address, $notice_portion ) {
		
		$_POST[ 'decred-refund-address' ] = $address;
		$this->assertFalse( $this->gateway->validate_fields(), "Failed FALSE address: $address" );
		
		$notices = WC()->session->get( 'wc_notices', array() );
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertTrue( count($notices["error"]) > 0 ); 
		$last_notice = array_pop($notices["error"]);
		$this->assertContains( $notice_portion, $last_notice );
	}
	
	public function test_validate_refund_address() {
		
		$g = $this->gateway;
		
		$tests = [
			[ null, false ],
			[ true, false ],
			[ 987.123, false ],
			[ '', false ],
			[ 'aaabbbccc', false ],
			[ 'DcrPwFMQW8v4FpHn2BBWiyVm7wr8HUgTcuc', true ],
		];
		
		foreach ( $tests as $test ) {
			$address = $test[0];
			$result = $test[1];
			$this->assertEquals( $g->validate_refund_address( $address ), $result, "Failing test address: $address" );
		}

	}

}
