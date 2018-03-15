<?php

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

// TODO replace with JS formatting as in Magento plugin
$amount_big = floor($dcr_amount*100)/100;
$amount_small = sprintf('%01.7f', round($dcr_amount - $amount_big, 7));
$amount_small = substr( $amount_small, 4, 5 );

?>	
<div class="payment_box payment_method_decred" style=""></p>

	<span style="text-align:center"><?= __('Amount to pay:','decred') ?></span>
		
	<span class="decred-label"  style="text-align:center">
		<span class="decred-price">
			<span class="decred-amount decred-amount__big" data-bind="text: displayDecredAmountBig"><?= $amount_big ?></span><span class="decred-amount decred-amount__small"><span data-bind="text: displayDecredAmountSmall"><?= $amount_small ?>&nbsp;DCR</span></span>
		</span>
	</span>
	
	
<?php 
/* TODO implement refund address...
		
	<fieldset id="wc-decred-crypto-form" class="wc-credit-card-form wc-payment-form">
		<p class="form-row form-row-wide woocommerce-validated">
		<label for="decred-card-number">ADDRESS <span class="required">*</span></label>
		<input id="decred-card-number" class="input-text wc-credit-card-form-card-number" 
			inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no"
			spellcheck="no" placeholder="•••• •••• •••• ••••" name="decred-card-number" type="tel">
		</p>

		<p class="form-row form-row-last woocommerce-validated">
		<label for="decred-card-cvc">Card code <span class="required">*</span></label>
		<input id="decred-card-cvc" class="input-text wc-credit-card-form-card-cvc" 
			inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" 
			spellcheck="no" maxlength="4" placeholder="CVC" name="decred-card-cvc" 
			style="width:100px" type="tel">
		</p>	


        <div class="checkout-decred-price">
            <!-- ko if: isShowRefundAddress() -->
            <p>Please enter below your Decred address in case you need a refund.</p>
            <dl class="decred-refund-address-wrapper">
                <dt><label for="decred-refund-address">Refund Address</label></dt>
                <dd>
                    <input id="decred-refund-address" name="refund_address" data-bind="attr: {required: !isRefundAddressOptional()}" required="true" type="text">
                    <div id="decred-refund-address-error" class="decred-error-wrapper"><span class="decred-error">Please enter valid Decred address</span></div>
                </dd>
            </dl>
            <!-- /ko -->
        </div>


		<div class="clear"></div>
	</fieldset>
*/ 
?>
	
</div>

