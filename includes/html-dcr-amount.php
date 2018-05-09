<?php
/**
 * Show the DCR amount with a special format of smaller digits after the third decimal digit
 */

/** @var float $dcr_amount $big_digits */
$big_digits   = floor( $dcr_amount * 100 ) / 100;
$small_digits = sprintf( '%01.7f', round( $dcr_amount - $big_digits, 7 ) );
$small_digits = substr( $small_digits, 4, 5 );

?>
<span class="decred-price">
	<span class="decred-amount decred-amount__big"><?php echo $big_digits; ?><span class="decred-amount decred-amount__small"><?php echo $small_digits; ?>&nbsp;</span>DCR</span>
</span>
