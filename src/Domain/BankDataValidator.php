<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\BankDataValidationResult as Result;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

class BankDataValidator {

	/** @var array<string, int> */
	private array $maximumFieldLengths = [
		Result::SOURCE_BANK_ACCOUNT => 10,
		Result::SOURCE_BANK_CODE => 8,
		Result::SOURCE_BANK_NAME => 250,
	];

	private IbanValidator $ibanValidator;

	/** @var ConstraintViolation[] */
	private array $violations;

	public function __construct( IbanValidator $ibanValidator ) {
		$this->ibanValidator = $ibanValidator;
	}

	public function validate( ExtendedBankData $bankData ): ValidationResult {
		$this->violations = [];

		if ( $bankData->iban->toString() === '' ) {
			$this->violations[] = new ConstraintViolation( '', Result::VIOLATION_MISSING, Result::SOURCE_IBAN );
		}
		$this->validateBic( $bankData->bic );
		$this->validateFieldLength( $bankData->bankName, Result::SOURCE_BANK_NAME );

		if ( $bankData->iban->getCountryCode() === 'DE' ) {
			$this->validateFieldLength( $bankData->account, Result::SOURCE_BANK_ACCOUNT );
			$this->validateFieldLength( $bankData->bankCode, Result::SOURCE_BANK_CODE );
		}

		$this->validateIban( $bankData->iban );

		return new ValidationResult( ...$this->violations );
	}

	private function validateBic( string $bic ): void {
		// see https://en.wikipedia.org/wiki/ISO_9362
		if ( $bic === '' || preg_match( '/^[A-Z]{6}[2-9A-Z][0-9A-NP-Z](XXX|[0-9A-WYZ][0-9A-Z]{2})?$/', $bic ) ) {
			return;
		}
		$this->violations[] = new ConstraintViolation(
			$bic,
			Result::VIOLATION_INVALID_BIC,
			Result::SOURCE_BIC
		);
	}

	private function validateFieldLength( string $value, string $fieldName ): void {
		if ( strlen( $value ) > $this->maximumFieldLengths[$fieldName] ) {
			$this->violations[] = new ConstraintViolation( $value, Result::VIOLATION_WRONG_LENGTH, $fieldName );
		}
	}

	private function validateIban( Iban $iban ): void {
		$ibanValidationResult = $this->ibanValidator->validate( $iban->toString() );
		if ( $ibanValidationResult->hasViolations() ) {
			$this->violations = array_merge(
				$this->violations,
				array_map(
					static function ( ConstraintViolation $violation ) {
						$violation->setSource( Result::SOURCE_IBAN );
						return $violation;
					},
					$ibanValidationResult->getViolations()
				)
			);
		}
	}

}
