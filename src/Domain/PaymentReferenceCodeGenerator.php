<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

abstract class PaymentReferenceCodeGenerator {
	/**
	 * @var string[]
	 */
	protected array $characters;

	protected int $characterCount;

	public function __construct() {
		$this->characters = str_split( PaymentReferenceCode::ALLOWED_CHARACTERS );
		$this->characterCount = count( $this->characters );
	}

	public function newPaymentReference( string $prefix ): PaymentReferenceCode {
		$code = $this->generateCode();

		return new PaymentReferenceCode(
			$prefix,
			$code,
			$this->calculateChecksum( $prefix . $code )
		);
	}

	private function generateCode(): string {
		$code = '';
		for ( $i = 0; $i < PaymentReferenceCode::LENGTH_CODE; $i++ ) {
			$code .= $this->characters[ $this->getNextCharacterIndex() ];
		}
		return $code;
	}

	private function calculateChecksum( string $prefixAndCode ): string {
		$checksumGenerator = new ChecksumGenerator( $this->characters );
		return $checksumGenerator->createChecksum( $prefixAndCode );
	}

	abstract protected function getNextCharacterIndex(): int;
}
