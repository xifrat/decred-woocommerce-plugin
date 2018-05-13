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

	const ICON_PATH   = '/assets/images/decred_logotext_2.svg';
	const STYLES_PATH = '/assets/styles.css';

	const JS_PATH_QRCODE = '/assets/qrcode.min.js';
	const JS_PATH_MAIN   = '/assets/decred.js';

	const CRON_INTERVAL = 60;
}
