<?php
/**
 * Intermediary class for the Decred PHP API (decred-php-api)
 *
 * TODO if performance hit turns out to be significant consider caching the extended key object and
 * resetting it if the master key changed in admin. Currently re-created every time a DCR order is placed.
 */

namespace Decred\Payments\WooCommerce;

use Decred\Crypto\ExtendedKey;

defined( 'ABSPATH' ) || exit;  // prevent direct URL execution.

/**
 * Decred Payments
 *
 * API Helper.
 *
 * @class       Decred\Payments\WooCommerce\API_Helper
 * @version     0.1
 * @author      xifrat
 */
class API_Helper {

	/**
	 * Master public key (BIP32).
	 *
	 * @var string master_public_key
	 */
	private $master_public_key;

	/**
	 * Extended key object.
	 *
	 * @var ExtendedKey $extended_key
	 */
	private $extended_key;

	/**
	 * Constructor.
	 *
	 * @param string $master_public_key master public key. TODO validate or guarantee it's correct. Or use a cached ExtendedKey object.
	 */
	public function __construct( $master_public_key ) {
		$this->master_public_key = $master_public_key;
	}

	/**
	 * Get extended key object in use.
	 *
	 * @return ExtendedKey
	 */
	public function get_extended_key() {
		if ( null === $this->extended_key ) {
			$this->extended_key = ExtendedKey::fromString( $this->master_public_key );
		}

		return $this->extended_key;
	}

	/**
	 * Get a DCR receiving address.
	 *
	 * @param int $index for the child key.
	 * @return string The corresponding address for the child index of the default account 0.
	 * @throws \Exception If $index not a positive number less tham 2^31.
	 * @throws \Exception Errors from decred-php-api.
	 */
	public function get_payment_address( $index ) {

		if ( ! is_numeric( $index ) || $index < 0 || $index >= 2 ** 31 ) { // TODO this check probably better moved to decred-php-api.
			throw new \Exception(
				'BIP32 child address index should be a positive number less tham 2^31.'
				. 'Received value "' . $index . '"'
			);
		}

		$address = $this->get_extended_key()
			->publicChildKey( 0 )
			->publicChildKey( $index )
			->getAddress();

		return $address;
	}

	/**
	 * Get transactions for a specific address after a specific point in time.
	 *
	 * @param string $address Decred address to query.
	 * @param int    $timestamp Query only after this point in time.
	 * @return array|boolean|\Decred\Data\Transaction[]
	 */
	public function get_transactions( $address, $timestamp ) {

		try {
			$transactions = $this->get_extended_key()
				->getNetwork()
				->getDataClient()
				->getAddressRaw( $address, $timestamp );

		} catch ( \Exception $e ) {
			// TODO catch specific "no transactions yet" exception + different treatment for others
			// TODO log $e.
			$transactions = [];
		}

		return $transactions;
	}

}
