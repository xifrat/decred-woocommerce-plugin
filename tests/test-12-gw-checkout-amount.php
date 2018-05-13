<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Checkout_Amount extends Gateway_TestCase {

	public function test_dcr_amount() {

		$g = $this->gateway;

		$cart_amount = 123.456;
		$eur_xrate   = `curl --silent --insecure https://api.coinmarketcap.com/v1/ticker/decred/?convert=EUR | grep price_eur | awk '{ print $2 }' | tr -d '",'`;
		$eur_xrate   = trim( $eur_xrate );
		$curl_amount  = $cart_amount / $eur_xrate;

		$g->fake_set_currency( 'EUR' );
		$g->fake_set_cart_amount( $cart_amount );

		$html = $this->get_html( 'payment_fields' );
		
		// extract amount from HTML 
		$lines = explode( PHP_EOL, $html );
		$grep  = preg_grep( '/decred-amount/', $lines );
		$html_amount = trim( strip_tags( array_pop($grep) ) );
		$suffix_index = strpos( $html_amount, '&nbsp;DCR' );
		$html_amount = substr( $html_amount, 0, $suffix_index );

		// Higher precision would provoke the test to fail due to
		// slight differences in rate returned between API calls.
		$precision = 5;
		$this->assertEquals( round( $html_amount, $precision ), round( $curl_amount, $precision ) );

		$this->assertEquals( WC()->session->get( 'decred_amount' ), $html_amount );

		// yet another CSS styles check // TODO why doesn't it work any more?
		// $this->assertTrue( wp_style_is( 'decred-styles' ) );
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
