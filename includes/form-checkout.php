<?php
/**
 * HTML portion of the checkout page that shows the Decred payment option
 * and eventually a form with a refund address.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

// TODO replace with JS formatting as in Magento plugin.
$amount_big   = floor( $dcr_amount * 100 ) / 100;
$amount_small = sprintf( '%01.7f', round( $dcr_amount - $amount_big, 7 ) );
$amount_small = substr( $amount_small, 4, 5 );

?>
	<span class="decred-label">
		<span><?php echo __( 'Amount to pay:', 'decred' ); ?>&nbsp;</span>
		<span class="decred-price" style="color: black;">
			<span class="decred-amount decred-amount__big" data-bind="text: displayDecredAmountBig"><?php echo $amount_big; ?><span class="decred-amount decred-amount__small" data-bind="text: displayDecredAmountSmall"><?php echo $amount_small; ?>&nbsp;</span>DCR</span>
		</span>
	</span>
<?php
if ( $this->show_refund_address() ) {
?>
	<fieldset id="wc-decred-crypto-form" class="wc-payment-form">
	<div class="form-row form-row-wide 
				<?php
				if ( $this->require_refund_address() ) {
?>
validate-required woocommerce-invalid<?php } ?>
				checkout-decred-price">
		<dl class="decred-refund-address-wrapper">
			<dt>
				<label for="decred-refund-address">
				<br><?php echo __( 'Decred address for refunds', 'decred' ); ?>
				<?php
				if ( $this->require_refund_address() ) {
?>
<span class="required">*</span><?php } ?>
				</label>
			</dt>
			<dd>
				<input id="decred-refund-address" name="decred-refund-address"
					class="input-text" style="width: 100%;" <?php /* data-bind="attr: {required: !isRefundAddressOptional()}" TODO implement JS required validation */ ?> 
					autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no"
					required="true" type="text">	
					<?php

					/*
					TODO implement JS required validation
					<div id="decred-refund-address-error" class="decred-error-wrapper">
					<span class="decred-error"><?= __('Please enter a valid Decred address','decred') ?></span>
					</div>
					*/
?>
			</dd>
		</dl>
	</div>
	</fieldset>
<?php
}
