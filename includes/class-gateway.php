<?php
/**
 * Payment Gateway class as required by WooCommerce
 *
 * Class split into several to group related methods:
 *
 * Gateway extends GW_Thankyou
 *         extends GW_Checkout
 *         extends GW_Base
 *         extends WC_Payment_Gateway
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 * Decred Payments
 *
 * @class       Decred\Payments\WooCommerce\Gateway
 * @extends     GW_Thankyou
 * @version     0.1
 * @author      xifrat
 */
class Gateway extends GW_Thankyou {

}
