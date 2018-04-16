<?php

namespace Decred\Payments\WooCommerce\Test;

abstract class Base_TestCase extends \WP_UnitTestCase {

	const TEST_MPK = 'tpubVooPRcVnuzBcqypmxFvcU2wfuHDrdq9QB6xHCgwPkGQssALSA2j96HG41EYDzuj1a1taYqMiiMTCF8L4ExtZ1199rNJMdeMcFyPziLf4LmK';
	
	const MAIN_MPK = 'dpubZFH1kErmwA1ZoDJcqLJ5KHH5kFtBUDVPHFUMQpLaMYCmdWszRFY4nMaZYmn3VK7ewndHrC8D5Hx1pfWSq6jQuMq8NEzmueWMBxqnYUXMgEh';
	
	public function actions_testcase( $actions, $object, $callbacks_depth = 1 ) {

		global $wp_filter;

		foreach ( $actions as $action ) {
			// actions get saved in $wp_filter as WP_Hook objects.
			$this->assertArrayHasKey( $action, $wp_filter );
			$this->assertEquals( 'WP_Hook', get_class( $wp_filter[ $action ] ) );
			// verify hook's class & method.
			$arr = array_shift( $wp_filter[ $action ]->callbacks );
			// echo "*** "; print_r(array_keys($arr));
			// by default we check the last callback.
			// in special cases there may be same-name filters added on top so we skip them
			for ( $i = 1; $i <= $callbacks_depth; $i++ ) {
				$arr2 = array_pop( $arr ); 
			}
			$arr3 = array_shift( $arr2 );
			$this->assertEquals( get_class( $object ), get_class( $arr3[0] ) );
			$this->assertTrue(
				method_exists( $arr3[0], $arr3[1] ),
				'Missing method ' . $arr3[1]
				);
		}
	}

}