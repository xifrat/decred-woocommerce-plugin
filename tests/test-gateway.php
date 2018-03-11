<?php

namespace Decred\Payments\WooCommerce\Test;

class Gateway extends \WP_UnitTestCase {

	function setUp() {
		$this->gateway = new \Decred\Payments\WooCommerce\Gateway;
	}

	function test_constructor() {
		$g = $this->gateway;

		$properties = array( 'id', 'method_title', 'method_description', 'title', 'description' );
		foreach ( $properties as $property ) {
			$this->assertNotEmpty( $g->$property );
		}

		$properties = array( 'icon', 'has_fields', 'instructions' );
		foreach ( $properties as $property ) {
			$this->assertTrue( isset( $g->$property ) );
		}
		
		$this->assertEquals( $g->id, 'decred' );
		$this->assertEquals( $g->icon, plugins_url( '/assets/images/decred_logotext.svg', dirname(__FILE__) ) );
		$this->assertEquals( $g->has_fields, false );

	}

}
