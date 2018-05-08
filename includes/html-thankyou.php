<?php
/**
 * Part of the "thank you page" that shows after pressing the pay button in the checkout page
 *
 * TODO COMPLETE with JavaScript features similar to the Magento plugin.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

wp_enqueue_script( 'decred-qrcode', plugins_url( $this->plugin->name  . Constant::JS_PATH_QRCODE ) );

wp_enqueue_script( 'decred-main', 	plugins_url( $this->plugin->name  . Constant::JS_PATH_MAIN ) );

/**
 * Ajax request for retrieving order status update.
 */
wp_localize_script('decred-main', 'ajax_action', array(
    'url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('ajaxnonce')
));

/** @var GW_Thankyou $this */
?>

<h2><?php echo __( 'Payment Details', 'decred' ); ?></h2>

<div id="decred-order-pay" class="decred-pay">

	<div class="decred-pay-header">
		<img src="<?php echo $this->icon; ?>" width="153" height="28">
	</div>
	<div class="decred-pay-content">
        <input type="hidden" id="decred-order-id" value="<?php echo $order_id ?>" />
        <input type="hidden" id="decred-order-status" value="<?php echo $this->dcr_order_status ?>" />
		<div class="decred-pay-qrcode" id="decred-qrcode" data-code="<?php echo $this->dcr_code; ?>"></div>
		<div class="decred-pay-info">
            <div class="decred-pay-row decred-pay-row_head">
                <span>Send exact amount to the address:</span>
                <button class="decred-pay-status decred-pay-status__paid" <?php echo $this->dcr_order_status !== 3 ? 'style="display: none"' : ''; ?>><i class="decred-icon_confirmed">&#10003;</i>Confirmed</button>
                <button class="decred-pay-status decred-pay-status__processing" <?php echo $this->dcr_order_status !== 2 ? 'style="display: none"' : ''; ?>><i class="decred-icon_dots">...</i>Processing</button>
                <button class="decred-pay-status decred-pay-status__pending" <?php echo $this->dcr_order_status !== 1 ? 'style="display: none"' : ''; ?>><i class="decred-icon_dots">...</i>Pending</button>
            </div>

            <?php
            if ( $this->instructions ) {
                echo '<div class="decred-pay-row"><span class="decred-pay-row__instructions">';
                echo wptexturize( $this->instructions );
                echo '</span></div>';
            }
            ?>
            <div class="decred-pay-row decred-pay-row__amount">
                <label><?php echo __( 'Exact amount to send:', 'decred' ); ?></label>
                <pre class="decred-pay-info-field">
                    <?php require __DIR__ . '/html-dcr-amount.php'; ?>
                    <i class="decred-icon_copy" data-bind="click: copyAmount"></i>
                </pre>
            </div>

            <div class="decred-pay-row">
                <label><?php echo __( 'Destination address:', 'decred' ); ?></label>
                <pre class="decred-pay-info-field">
                    <span><?php echo $this->dcr_payment_address; ?></span>
                    <i class="decred-icon_copy" data-bind="click: copyAddress"></i>
                </pre>
            </div>

        </div>

	</div>

</div>
