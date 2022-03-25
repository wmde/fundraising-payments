<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use Iterator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

/**
 * @todo Rename to RandomTransferCodeGenerator, use TransferCode class
 */
class LessSimpleTransferCodeGenerator implements TransferCodeGenerator {

	/**
	 * @var Iterator<string>
	 */
	private Iterator $characterSource;
	private ChecksumGenerator $checksumGenerator;

	/**
	 * @param Iterator<string> $characterSource
	 */
	private function __construct( Iterator $characterSource ) {
		$this->characterSource = $characterSource;

		$this->checksumGenerator = new ChecksumGenerator( str_split( PaymentReferenceCode::ALLOWED_CHARACTERS ) );
	}

	public static function newRandomGenerator(): static {
		return new self(
			( static function (): iterable {
				$characterCount = strlen( PaymentReferenceCode::ALLOWED_CHARACTERS );
				$characters = str_split( PaymentReferenceCode::ALLOWED_CHARACTERS );
				// See https://github.com/phpstan/phpstan/issues/6189
				// @phpstan-ignore-next-line
				while ( true ) {
					yield $characters[mt_rand( 0, $characterCount - 1 )];
				}
			} )()
		);
	}

	/**
	 * @param Iterator<string> $characterSource
	 * @return self
	 */
	public static function newDeterministicGenerator( Iterator $characterSource ): self {
		return new self( $characterSource );
	}

	public function generateTransferCode( string $prefix ): string {
		return $this->generatePaymentReferenceCode( $prefix )->getFormattedCode();
	}

	public function generatePaymentReferenceCode( string $prefix ): PaymentReferenceCode {
		$code = $this->generateCode();
		return new PaymentReferenceCode(
			$prefix,
			$code,
			$this->checksumGenerator->createChecksum( $prefix . $code )
		);
	}

	private function generateCode(): string {
		$transferCode = '';

		for ( $i = 0; $i < PaymentReferenceCode::LENGTH_CODE; $i++ ) {
			$transferCode .= $this->characterSource->current();
			$this->characterSource->next();
		}

		return $transferCode;
	}

}
