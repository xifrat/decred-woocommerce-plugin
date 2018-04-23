<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Checkout_Amount extends Gateway_TestCase {

	public function test_dcr_amount() {

		$g = $this->gateway;

		$cart_amount = 123.456;
		$eur_xrate   = `curl --silent --insecure https://api.coinmarketcap.com/v1/ticker/decred/?convert=EUR | grep price_eur | awk '{ print $2 }' | tr -d '",'`;
		$eur_xrate   = trim( $eur_xrate );
		$dcr_amount  = $cart_amount / $eur_xrate;

		$g->fake_set_currency( 'EUR' );
		$g->fake_set_cart_amount( $cart_amount );
		$html = $this->get_html( 'payment_fields' );

		// Higher precision would provoke the test to fail due to
		// slight differences in rate returned between API calls.
		$precision = 4;
		$this->assertEquals( round( $g->dcr_amount, $precision ), round( $dcr_amount, $precision ) );

		$this->assertEquals( WC()->session->get( 'decred_amount' ), $g->dcr_amount );

		// yet another CSS styles check
		$this->assertTrue( wp_style_is( 'decred-1' ) );
	}

	public function test_dcr_amount_errors() {

		$g = $this->gateway;
		$g->fake_set_currency( 'USD' );

		global $woocommerce;

		// cart errors.
		unset( $woocommerce->cart );
		$this->assertHtmlError( 'does not exist' );

		$woocommerce->cart = 'this is a string';
		$this->assertHtmlError( 'does not exist' );

		$woocommerce->cart = new Fake_Empty();
		$this->assertHtmlError( 'does not exist' );

		$woocommerce->cart = new Fake_Cart( null );
		$this->assertHtmlError( 'not a positive' );

		$woocommerce->cart = new Fake_Cart( 'aaabbbccc' );
		$this->assertHtmlError( 'not a positive' );

		$woocommerce->cart = new Fake_Cart( 0 );
		$this->assertHtmlError( 'not a positive' );

		// currency errors.
		$g->fake_set_currency( 'fake' );
		$woocommerce->cart = new Fake_Cart( 456.321 );
		$this->assertHtmlError( 'Missing currency' );
	}

	private function assertHtmlError( $string ) {
		$html = $this->get_html( 'payment_fields' );
		$this->assertRegExp( '/.*' . $string . '.*/', $html );
	}
}
