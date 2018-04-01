<?php

namespace Decred\Payments\WooCommerce\Test;

require_once dirname( __DIR__ ) . '/includes/class-constant.php';
use Decred\Payments\WooCommerce\Constant;

class Plugin extends \WP_UnitTestCase {

	function test_global_decred_wc_plugin() {
		global $decred_wc_plugin;
		$this->assertTrue( isset( $decred_wc_plugin ) );
		$this->assertEquals( get_class( $decred_wc_plugin ), 'Decred\Payments\WooCommerce\Plugin' );
	}

	function test_gateway_class_exists() {
		$this->assertTrue( class_exists( 'Decred\Payments\WooCommerce\Gateway' ) );
	}

	function test_logger_set() {
		global $decred_wc_plugin;
		$this->assertTrue( isset( $decred_wc_plugin->logger ) );
		$this->assertEquals( get_class( $decred_wc_plugin->logger ), 'WC_Logger' );
	}

	function test_is_operational() {
		global $decred_wc_plugin;
		$this->assertTrue( isset( $decred_wc_plugin->operational ) );
		$this->assertTrue( $decred_wc_plugin->operational );
	}

	function test_payment_method_added() {
		$methods = apply_filters( 'woocommerce_payment_gateways', array() );
		$this->assertTrue( count( $methods ) == 1 );
		$this->assertEquals( $methods[0], 'Decred\Payments\WooCommerce\Gateway' );
	}

	function test_action_links_added() {
		global $decred_wc_plugin;
		$hook  = 'plugin_action_links_' . $decred_wc_plugin->name;
		$links = apply_filters( $hook, array() );
		$this->assertTrue( count( $links ) == 2 );
		$this->assertContains( 'Settings', $links[0] );
		$this->assertContains( 'Logs', $links[1] );
	}

	function test_plugin_headers_ok() {
		global $decred_wc_plugin;
		$plugin_data = get_plugin_data( $decred_wc_plugin->file );

		$this->assertEquals( 'decred', $plugin_data['TextDomain'] );
	}
}
