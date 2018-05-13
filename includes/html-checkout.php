<?php
/**
 * HTML portion of the checkout page that shows the Decred payment option
 * and eventually a form with a refund address.
 */

namespace Decred\Payments\WooCommerce;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

?>
	<span class="decred-label">
		<span><?php echo __( 'Amount to pay:', 'decred' ); ?>&nbsp;</span>
		<?php require __DIR__ . '/html-dcr-amount.php'; ?>
	</span>
	<?php if ( $this->show_refund_address() ) : ?>
		<fieldset id="wc-decred-crypto-form" class="wc-payment-form">
		<div class="form-row form-row-wide <?php echo $this->require_refund_address() ? 'validate-required woocommerce-invalid' : ''; ?> checkout-decred-price">
			<dl class="decred-refund-address-wrapper">
				<dt>
					<label for="decred-refund-address">
					<br><?php echo __( 'Decred address for refunds', 'decred' ); ?>
						<?php if ( $this->require_refund_address() ) : ?>
							<span class="required">*</span>
						<?php endif; ?>
					</label>
				</dt>
				<dd>
					<input id="decred-refund-address" name="decred-refund-address"
						class="input-text" style="width: 100%;"
						autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no"
						required="true" type="text">
				</dd>
			</dl>
		</div>
		</fieldset>
	<?php endif; ?>
