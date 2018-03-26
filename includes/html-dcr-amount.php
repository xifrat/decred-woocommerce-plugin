<?php
/**
 * Show the DCR amount with a special format of smaller digits after the third decimal digit
 */

?>
<span class="decred-price">
	<span class="decred-amount decred-amount__big"><?php echo $this->dcr_big_digits; ?><span class="decred-amount decred-amount__small"><?php echo $this->dcr_small_digits; ?>&nbsp;</span>DCR</span>
</span>
