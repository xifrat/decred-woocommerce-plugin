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

		<div class="decred-pay-row decred-pay-row__title">
			<span><?php echo __( 'Payment Details', 'decred' ); ?></span>
		</div>

		<?php
		if ( $this->instructions ) {
			echo '<div class="decred-pay-row"><span class="decred-pay-row__instructions">';
			echo wptexturize( $this->instructions );
			echo '</span></div>';
		}
		?>
		<div class="decred-pay-row">
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
