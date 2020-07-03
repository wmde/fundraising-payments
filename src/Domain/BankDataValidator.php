<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\BankDataValidationResult as Result;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class BankDataValidator {

	private $maximumFieldLengths = [
		Result::SOURCE_BANK_ACCOUNT => 10,
		Result::SOURCE_BANK_CODE => 8,
		Result::SOURCE_BANK_NAME => 250,
	];

	private $ibanValidator;

	private $violations;

	public function __construct( IbanValidator $ibanValidator ) {
		$this->ibanValidator = $ibanValidator;
	}

	public function validate( BankData $bankData ): ValidationResult {
		$this->violations = [];

		if ( $bankData->getIban()->toString() === '' ) {
			$this->violations[] = new ConstraintViolation( '', Result::VIOLATION_MISSING, Result::SOURCE_IBAN );
		}
		$this->validateBic( $bankData->getBic() );
		$this->validateFieldLength( $bankData->getBankName(), Result::SOURCE_BANK_NAME );

		if ( $bankData->getIban()->getCountryCode() === 'DE' ) {
			$this->validateFieldLength( $bankData->getAccount(), Result::SOURCE_BANK_ACCOUNT );
			$this->validateFieldLength( $bankData->getBankCode(), Result::SOURCE_BANK_CODE );
		}

		$this->validateIban( $bankData );

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

	private function validateIban( BankData $bankData ): void {
		$ibanValidationResult = $this->ibanValidator->validate( $bankData->getIban() );
		if ( $ibanValidationResult->hasViolations() ) {
			$this->violations = array_merge(
				$this->violations,
				array_map(
					function ( ConstraintViolation $violation ) {
						$violation->setSource( Result::SOURCE_IBAN );
						return $violation;
					},
					$ibanValidationResult->getViolations()
				)
			);
		}
	}

}
