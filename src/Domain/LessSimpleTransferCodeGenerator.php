<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\PaymentContext\Domain;

use InvalidArgumentException;

/**
 * @licence GNU GPL v2+
 */
class LessSimpleTransferCodeGenerator implements TransferCodeGenerator {

	public const ALLOWED_CHARACTERS = 'ACDEFKLMNPRTWXYZ349';
	public const READABILITY_DELIMITER = '-';

	public const LENGTH_PREFIX = 2;
	public const LENGTH_CODE = 6;
	public const LENGTH_CHECKSUM = 1;

	private $characterSource;
	private $checksumGenerator;

	private function __construct( \Iterator $characterSource ) {
		$this->characterSource = $characterSource;

		$this->checksumGenerator = new ChecksumGenerator( str_split( self::ALLOWED_CHARACTERS ) );
	}

	public static function newRandomGenerator(): self {
		return new self(
			( function() {
				$characterCount = strlen( self::ALLOWED_CHARACTERS );
				$characters = str_split( self::ALLOWED_CHARACTERS );
				while ( true ) {
					yield $characters[mt_rand( 0, $characterCount - 1 )];
				}
			} )()
		);
	}

	public static function newDeterministicGenerator( \Iterator $characterSource ): self {
		return new self( $characterSource );
	}

	public function generateTransferCode( string $prefix ): string {
		if ( strlen( $prefix ) !== self::LENGTH_PREFIX ) {
			throw new InvalidArgumentException(
				sprintf(
					'The prefix must have a set length of %d characters.',
					self::LENGTH_PREFIX
				)
			);
		}
		if ( !preg_match( '/[' . preg_quote( self::ALLOWED_CHARACTERS ) . ']/', $prefix ) ) {
			throw new InvalidArgumentException(
				'The prefix must only contain characters from the ALLOWED_CHARACTERS set.'
			);
		}

		$code = $prefix . $this->generateCode();
		$code .= $this->checksumGenerator->createChecksum( $code );

		return $this->formatCodeForReadability( $code );
	}

	private function formatCodeForReadability( string $code ): string {
		return vsprintf( '%s%s-%s%s%s-%s%s%s-%s', str_split( $code ) );
	}

	private function generateCode(): string {
		$transferCode = '';

		for ( $i = 0; $i < self::LENGTH_CODE; $i++ ) {
			$transferCode .= $this->characterSource->current();
			$this->characterSource->next();
		}

		return $transferCode;
	}

}