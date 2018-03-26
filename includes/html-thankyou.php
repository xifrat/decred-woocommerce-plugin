<?php
/**
 * Part of the "thank you page" that shows after pressing the pay button in the checkout page
 *
 * TODO IMPLEMENT PROPERLY, this just pasted from Magento plugin.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

?>

<div id="decred-order-pay" class="decred-pay">

	<div>
	<span class="decred-pay-logo">
		<img src="<?php echo $this->icon; ?>" width="153" height="28">
	</span>
	</div>

	<div class="decred-pay-content">

			<div class="decred-pay-row">
				<span><?php echo __( 'Payment Details', 'decred' ); ?></span>				
			</div>

			<div class="decred-pay-row decred-pay-row__amount">
				<label><?php echo __( 'Exact amount to send:', 'decred' ); ?></label>
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
				<label><?php echo __( 'Destination address:', 'decred' ); ?></label>
				<pre class="decred-pay-info-field">                    <span data-bind="text: address">Dsj2oAg56UStZKaAPUWbbirz3Gap9GxsJFc</span>
					<i class="decred-icon_copy" data-bind="click: copyAddress"></i>
				</pre>
			</div>


	</div>
	
</div>