<?php

namespace Decred\Payments\WooCommerce\Test;

require_once dirname( __DIR__ ) . '/includes/class-constant.php';
use Decred\Payments\WooCommerce\Constant;

require_once 'class-gateway-testcase.php';

class Gateway_Constructor extends Gateway_TestCase {

	public function test_constructor() {

		$g = $this->gateway;

		/**
		 *  Properties
		 */
		$properties = array( 'id', 'method_title', 'method_description', 'title', 'description' );
		foreach ( $properties as $property ) {
			$this->assertNotEmpty( $g->$property );
		}

		$properties = array( 'icon', 'has_fields', 'instructions' );
		foreach ( $properties as $property ) {
			$this->assertTrue( isset( $g->$property ) );
		}

		$this->assertEquals( $g->id, Constant::CURRENCY_ID );
		$this->assertEquals( $g->icon, plugins_url( Constant::ICON_PATH, dirname( __FILE__ ) ) );
		$this->assertEquals( $g->has_fields, true );
		$this->assertEquals( $g->method_title, Constant::CURRENCY_NAME );
		$this->assertEquals( $g->method_description, 'Allows direct payments with the Decred cryptocurrency.' );
		$this->assertEquals( $g->order_button_text, 'Pay with Decred' );

		/**
		 * Form fields
		 */
		// setup form should have these fields.
		$form_field_names = array( 'enabled', 'title', 'description', 'instructions', 'show_refund_address', 'refund_address_optional' );
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
		 *
		 * Note these tests are very implementation dependant,
		 * they might break in future WP/WC versions
		 */
		global $wp_filter;

		$actions = array(
			'woocommerce_update_options_payment_gateways_' . $g->id,
			'woocommerce_thankyou_' . $g->id,
			'woocommerce_email_before_order_table',
			'wp_enqueue_scripts',
		);

		foreach ( $actions as $action ) {
			// actions get saved in $wp_filter as WP_Hook objects.
			$this->assertArrayHasKey( $action, $wp_filter );
			$this->assertEquals( 'WP_Hook', get_class( $wp_filter[ $action ] ) );
			// verify hook's class & method.
			$arr = array_shift( $wp_filter[ $action ]->callbacks );
			// echo "*** "; print_r(array_keys($arr)); .
			$arr = array_pop( $arr ); // we check the last callback.
			$arr = array_shift( $arr );
			$this->assertEquals( get_class( $g ), get_class( $arr[0] ) );
			$this->assertTrue(
				method_exists( $arr[0], $arr[1] ),
				'Missing method ' . $arr[1]
			);

		}

	}

}
