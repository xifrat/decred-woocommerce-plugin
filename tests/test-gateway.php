<?php

namespace Decred\Payments\WooCommerce\Test;

include "../includes/class-constant.php";
use Decred\Payments\WooCommerce\Constant;

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
		
		$this->assertEquals( $g->id, Constant::CURRENCY_ID );
		$this->assertEquals( $g->icon, plugins_url( Constant::ICON_PATH, dirname(__FILE__) ) );
		$this->assertEquals( $g->has_fields, false );
		$this->assertEquals( $g->method_title, Constant::CURRENCY_NAME );
		$this->assertEquals( $g->method_description, 'Allows direct payments with the Decred cryptocurrency.' );
		//$this->assertEquals( $g->title, $g->method_title );
	}

}
