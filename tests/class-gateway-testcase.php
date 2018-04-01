<?php

namespace Decred\Payments\WooCommerce\Test;

class Fake_Empty {
}

class Fake_Cart extends Fake_Empty {
	public function __construct( $total ) {
		$this->total = $total;
	}
	public function get_total() {
		return $this->total;
	}
}

class Fake_Gateway extends \Decred\Payments\WooCommerce\Gateway {

	// test-only methods.
	public function fake_set_currency( $currency ) {
		$this->currency = $currency;
	}
	public function fake_set_cart_amount( $amount ) {
		global $woocommerce;
		$woocommerce->cart = new Fake_Cart( $amount );
	}

	// test method that overrrides original.
	protected function get_currency() {
		return $this->currency;
	}
}

abstract class Gateway_TestCase extends \WP_UnitTestCase {

	public function setUp() {
		$this->gateway = new Fake_Gateway;
	}

	protected function get_form_html() {
		ob_start();
		$this->gateway->payment_fields();
		$html = ob_get_contents();
		ob_end_clean();
		// echo $html; .
		return $html;
	}

}
