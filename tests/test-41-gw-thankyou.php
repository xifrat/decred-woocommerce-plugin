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

		$data = [
			'customer_id'    => 1,
			'status'         => 'wc-pending',
			'payment_method' => 'decred',
			'decred_amount'  => 234.5677777,
			'decred_payment_address' => 'FAKEFAKEFAKE' 
		];
		$order = $this->create_order( $data );

		$html = $this->get_html( 'thankyou_page', $order->get_id() );

		$txt = preg_replace( '/\s*/m', '', strip_tags( $html ) );
		$this->assertRegExp( '/.*amounttosend:234.5677777&nbsp;DCR.*/', $txt );

		$this->assertRegExp( '/.*<span>FAKEFAKEFAKE<\/span>.*/', strip_tags( $html, '<span>' ) );
	}

}
