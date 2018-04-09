<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Thankyou extends Gateway_TestCase {

	public function test_order_received_text() {
		$text = 'default text';
		$text = apply_filters( 'woocommerce_thankyou_order_received_text', $text );
		$this->assertRegExp( '/.*INSTRUCCIONS BELOW.*/', $text );
	}

	public function test_amount_and_address() {

		$g = $this->gateway;

		$order_id = 123;
		add_post_meta( $order_id, 'decred_amount', 234.5677777 );
		add_post_meta( $order_id, 'decred_payment_address', 'FAKEFAKEFAKE' );

		$html = $this->get_html( 'thankyou_page', $order_id );

		$txt = preg_replace( '/\s*/m', '', strip_tags( $html ) );
		$this->assertRegExp( '/.*amounttosend:234.5677777&nbsp;DCR.*/', $txt );

		$this->assertRegExp( '/.*<span>FAKEFAKEFAKE<\/span>.*/', strip_tags( $html, '<span>' ) );
	}

}
