<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-base-testcase.php';

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

	// test methods that overrride original ones.
	protected function get_currency() {
		return $this->currency;
	}

}

abstract class Gateway_TestCase extends Base_TestCase {

	public function setUp() {
		$this->gateway = new Fake_Gateway;
	}

	protected function get_html( $callback, $param = null ) {
		ob_start();
		call_user_func( [ $this->gateway, $callback ], $param );
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

}