<?php
/**
 * Part of the "thank you page" that shows after pressing the pay button in the checkout page
 *
 * TODO IMPLEMENT PROPERLY, this just pasted from Magento plugin.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

?>	

<div id="decred-order-pay" data-bind="scope:'decred-order-pay'">

<div data-role="checkout-messages" class="messages" data-bind="visible: isVisible(), click: removeAll">
</div>

<div class="decred-pay decred-pay__big" data-bind="attr: { class: componentClasses() }">
	<div class="decred-pay-header">
		<img data-bind="attr: { src: require.toUrl('Decred_Payments/images/decred_logo.png') }" src="http://decred-magento.r3volut1oner.com/static/version1520345785/frontend/Magento/luma/en_US/Decred_Payments/images/decred_logo.png" width="153" height="28">
	</div>
	<div class="decred-pay-content">
		<div class="decred-pay-qrcode" id="decred-qrcode" title="Dsj2oAg56UStZKaAPUWbbirz3Gap9GxsJFc"><canvas width="300" height="300" style="display: none;"></canvas><img style="display: block;" src="data:image/png;base64,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"></div>
		<div class="decred-pay-info">
			<div class="decred-pay-row decred-pay-row_head">
				<span>Send exact amount to the address:</span>
				<button class="decred-pay-status decred-pay-status__pending"><i class="decred-icon_dots">...</i>Pending</button>
			</div>
			<div class="decred-pay-row decred-pay-row__amount">
				<label>Amount</label>
				<pre class="decred-pay-info-field">                    <span class="decred-price">
						<span class="decred-amount decred-amount__big" data-bind="text: displayDecredAmountBig">0.01</span>
						<span class="decred-amount decred-amount__small">
							<span data-bind="text: displayDecredAmountSmall">684046</span><span>&nbsp;DCR</span>
						</span>
					</span>
					<i class="decred-icon_copy" data-bind="click: copyAmount"></i>
				</pre>
			</div>
			<div class="decred-pay-row decred-pay-row__address">
				<label>Address</label>
				<pre class="decred-pay-info-field">                    <span data-bind="text: address">Dsj2oAg56UStZKaAPUWbbirz3Gap9GxsJFc</span>
					<i class="decred-icon_copy" data-bind="click: copyAddress"></i>
				</pre>
			</div>
		</div>
	</div>
</div>

</div>
