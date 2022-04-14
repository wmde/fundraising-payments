<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator;

use WMDE\Fundraising\PaymentContext\Domain\CharacterIndexGenerator;
use WMDE\Fundraising\PaymentContext\Domain\ChecksumGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;

class RandomPaymentReferenceCodeGenerator implements PaymentReferenceCodeGenerator {

	private CharacterIndexGenerator $characterIndexGenerator;

	/**
	 * @var string[]
	 */
	protected array $characters;
	protected int $characterCount;
	private int $maxRandom;

	public function __construct( CharacterIndexGenerator $characterIndexGenerator ) {
		$this->characterIndexGenerator = $characterIndexGenerator;
		$this->characters = str_split( PaymentReferenceCode::ALLOWED_CHARACTERS );
		$this->characterCount = count( $this->characters );
		$this->maxRandom = count( $this->characters ) - 1;
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
			$code .= $this->characters[ $this->characterIndexGenerator->getNextCharacterIndex( $this->maxRandom ) ];
		}
		return $code;
	}

	private function calculateChecksum( string $prefixAndCode ): string {
		$checksumGenerator = new ChecksumGenerator( $this->characters );
		return $checksumGenerator->createChecksum( $prefixAndCode );
	}

}
