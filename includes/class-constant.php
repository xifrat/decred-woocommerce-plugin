<?php
/**
 *  Plugin Constants
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 *
 * Plugin constants container.
 *
 * @author xifrat
 */
class Constant {

	const CURRENCY_ID   = 'decred';
	const CURRENCY_NAME = 'Decred';

	const ICON_PATH = '/assets/images/decred_logotext.svg';
	// TODO JS features pending // const JS_PATHS = array( '/assets/require.js', '/assets/decred-method.js' ); 
	const STYLES_PATH = '/assets/styles.css';

}
