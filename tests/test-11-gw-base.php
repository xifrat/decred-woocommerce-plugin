<?php

namespace Decred\Payments\WooCommerce\Test;

require_once 'class-gateway-testcase.php';

class GW_Base extends Gateway_TestCase {

	public function test_constructor() {

		$g = $this->gateway;

		/**
		 *  Properties
		 */
		$properties = array( 'id', 'method_title', 'method_description', 'title' );
		foreach ( $properties as $property ) {
			$this->assertNotEmpty( $g->$property );
		}

		$properties = array( 'icon', 'has_fields', 'instructions' );
		foreach ( $properties as $property ) {
			$this->assertTrue( isset( $g->$property ) );
		}

		$this->assertEquals( $g->id, 'decred' );
		$this->assertEquals( $g->icon, 'http://example.org/wp-content/plugins/decred-woocommerce-plugin/assets/images/decred_logotext_2.svg' );
		$this->assertEquals( $g->has_fields, true );
		$this->assertEquals( $g->method_title, 'Decred' );
		$this->assertEquals( $g->method_description, 'Allows direct payments with the Decred cryptocurrency.' );
		$this->assertEquals( $g->order_button_text, 'Pay with Decred' );

		/**
		 * Form fields
		 */
		// setup form should have these fields.
		$form_field_names = array( 'enabled', 'master_public_key', 'title', 'instructions', 'show_refund_address', 'refund_address_optional' );
		$num_fields       = count( $form_field_names );
		$this->assertCount( $num_fields, $g->form_fields );

		// each field element should be an array with 3+ fields.
		foreach ( $form_field_names as $field_name ) {
			$this->assertGreaterThanOrEqual( 3, count( $g->form_fields[ $field_name ] ) );
		}

		/**
		 * Settings
		 */
		$this->assertEquals( $g->title, $g->get_option( 'title' ) );
		$this->assertEquals( $g->description, $g->get_option( 'description' ) );
		$this->assertEquals( $g->instructions, $g->get_option( 'instructions' ) );

		/**
		 * Actions
		 */
		$actions = array(
			'woocommerce_update_options_payment_gateways_' . $g->id,
			'woocommerce_thankyou_' . $g->id,
			'woocommerce_email_before_order_table',
			'wp_enqueue_scripts',
			'woocommerce_new_order',
			'woocommerce_thankyou_order_received_text',
		);
		$this->actions_testcase( $actions, $g );

		/**
		 * Assets
		 */

		// would be better to do_action( 'wp_enqueue_scripts' ) but had issues making it work.
		$g->wp_enqueue_assets();

		ob_start();
		wp_head();
		$html = ob_get_contents();
		ob_end_clean();

		$lines = explode( PHP_EOL, $html );
		$grep  = preg_grep( '/decred-/', $lines );
		$this->assertNotEmpty( $grep );
		$grep = preg_grep( '#/assets/styles.css#', $grep );
		$this->assertNotEmpty( $grep );
	}

}
