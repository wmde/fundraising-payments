<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

/**
 * Calculate a one-character checksum for a payment reference code
 */
class ChecksumGenerator {

	/** @var string[] */
	private array $checksumCharacters;

	/**
	 * @param string[] $checksumCharacters Characters that can be used for the checksum
	 */
	public function __construct( array $checksumCharacters ) {
		if ( count( $checksumCharacters ) < 2 ) {
			throw new \UnexpectedValueException(
				'Need at least two characters to create meaningful checksum'
			);
		}

		$this->checksumCharacters = $checksumCharacters;
	}

	/**
	 * @param string $string The string to create a checksum for
	 *
	 * @return string The checksum as a single character present in the constructors array argument
	 */
	public function createChecksum( string $string ): string {
		$checksum = md5( $string );
		$checkDigitSum = array_sum(
			array_map(
				fn( $digit ) => base_convert( $digit, 16, 10 ),
				str_split( $checksum )
			)
		);

		return $this->checksumCharacters[$checkDigitSum % count( $this->checksumCharacters )];
	}

}
