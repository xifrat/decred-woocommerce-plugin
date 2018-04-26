<?php
/**
 * PHPUnit bootstrap file
 */

echo "Bootstrap...\n";

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
$file = $_tests_dir . '/includes/functions.php';
echo "Including $file ...\n";
require_once $file;

/**
 * Manually load the WooCommerce plugin, then the Decred Payments plugin.
 */
function _manually_load_plugin() {
	require dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php';
	require dirname( __DIR__ ) . '/decred-woocommerce-plugin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
$file = $_tests_dir . '/includes/bootstrap.php';
echo "Including $file ...\n";
require_once $file;
