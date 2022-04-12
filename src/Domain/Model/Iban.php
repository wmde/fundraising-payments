<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

class Iban {

	private string $iban;

	public function __construct( string $iban ) {
		$this->iban = $this->sanitizeIban( $iban );
	}

	public function toString(): string {
		return $this->iban;
	}

	public function getCountryCode(): string {
		return substr( $this->iban, 0, 2 );
	}

	private function sanitizeIban( string $iban ): string {
		// There is no way our simple regex can return null, so let's appease PHPStan
		// @phpstan-ignore-next-line
		return preg_replace( '/[^0-9A-Z]/u', '', strtoupper( $iban ) );
	}
}
