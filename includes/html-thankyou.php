<?php
/**
 * Part of the "thank you page" that shows after pressing the pay button in the checkout page
 *
 * TODO COMPLETE with JavaScript features similar to the Magento plugin.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/** @var GW_Thankyou $this */
?>

<h2><?php echo __( 'Payment Details', 'decred' ); ?></h2>

<div id="decred-order-pay" class="decred-pay">

	<div class="decred-pay-header">
		<img src="<?php echo $this->icon; ?>" width="153" height="28">
	</div>
	<div class="decred-pay-content">
		<div class="decred-pay-qrcode" id="decred-qrcode" data-code="<?php echo $this->dcr_code; ?>"></div>
		<div class="decred-pay-info">
            <div class="decred-pay-row decred-pay-row_head">
                <span>Send exact amount to the address:</span>
                <button class="decred-pay-status decred-pay-status__paid"><i class="decred-icon_confirmed">&#10003;</i>Confirmed</button>
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
