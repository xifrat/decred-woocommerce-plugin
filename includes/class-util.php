<?php
/**
 *  Plugin Utilities
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 *
 * Class created to group together misc static utility methods.
 *
 * @author xifrat
 */
class Util {

	/**
	 * Replacement for __()
	 *
	 * @param string $text text to translate.
	 */
	public static function translate( $text ) {
		return __( $text, Constant::TEXT_DOMAIN );
	}
}
