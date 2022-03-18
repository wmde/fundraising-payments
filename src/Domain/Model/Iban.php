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

	public function accountNrFromDeIban(): string {
		return substr( $this->iban, 12 );
	}

	public function bankCodeFromDeIban(): string {
		return substr( $this->iban, 4, 8 );
	}

	public function getCountryCode(): string {
		return substr( $this->iban, 0, 2 );
	}

	private function sanitizeIban( string $iban ): string {
		return preg_replace( '/[^0-9A-Z]/u', '', strtoupper( $iban ) );
	}
}
