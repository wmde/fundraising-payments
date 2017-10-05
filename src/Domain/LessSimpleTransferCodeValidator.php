<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\PaymentContext\Domain;

class LessSimpleTransferCodeValidator {

	private $checksumGenerator;

	public function __construct() {
		$this->checksumGenerator = new ChecksumGenerator( str_split( LessSimpleTransferCodeGenerator::ALLOWED_CHARACTERS ) );
	}

	public function transferCodeIsValid( string $code ): bool {
		$code = strtoupper( $code );
		$code = preg_replace( '/[^' . preg_quote( LessSimpleTransferCodeGenerator::ALLOWED_CHARACTERS ).  ']/', '', $code );

		return $this->formatIsValid( $code )
			&& $this->checksumIsCorrect( $code );
	}

	private function formatIsValid( string $code ): bool {
		return strlen( $code ) ===
			LessSimpleTransferCodeGenerator::LENGTH_PREFIX +
			LessSimpleTransferCodeGenerator::LENGTH_CODE +
			LessSimpleTransferCodeGenerator::LENGTH_CHECKSUM;
	}

	private function checksumIsCorrect( string $code ): bool {
		return $this->checksumGenerator->createChecksum( substr( $code, 0, -LessSimpleTransferCodeGenerator::LENGTH_CHECKSUM ) )
			=== substr( $code, -LessSimpleTransferCodeGenerator::LENGTH_CHECKSUM );
	}

}
