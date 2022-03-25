<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

class LessSimpleTransferCodeValidator {

	private ChecksumGenerator $checksumGenerator;

	public function __construct() {
		$this->checksumGenerator = new ChecksumGenerator(
			str_split( PaymentReferenceCode::ALLOWED_CHARACTERS )
		);
	}

	public function transferCodeIsValid( string $code ): bool {
		$code = strtoupper( $code );
		$code = preg_replace(
			'/[^' . preg_quote( PaymentReferenceCode::ALLOWED_CHARACTERS ) . ']/',
			'',
			$code
		);

		return $this->formatIsValid( $code )
			&& $this->checksumIsCorrect( $code );
	}

	private function formatIsValid( string $code ): bool {
		return strlen( $code ) ===
			PaymentReferenceCode::LENGTH_PREFIX +
			PaymentReferenceCode::LENGTH_CODE +
			PaymentReferenceCode::LENGTH_CHECKSUM;
	}

	private function checksumIsCorrect( string $code ): bool {
		return $this->checksumGenerator->createChecksum(
				substr( $code, 0, -PaymentReferenceCode::LENGTH_CHECKSUM )
			)
			=== substr( $code, -PaymentReferenceCode::LENGTH_CHECKSUM );
	}

}
