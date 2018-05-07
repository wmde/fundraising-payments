<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\FunValidators\CanValidateField;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\RequiredFieldValidator;
use WMDE\FunValidators\Validators\StringLengthValidator;

/**
 * @license GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class BankDataValidator {
	use CanValidateField;

	public const INVALID_BIC = 'invalid_bic';

	private const BANK_ACCOUNT_MAXLEN = 10;
	private const BANK_CODE_MAXLEN = 8;
	private const BANK_NAME_MAXLEN = 250;

	private $ibanValidator;

	public function __construct( IbanValidator $ibanValidator ) {
		$this->ibanValidator = $ibanValidator;
	}

	public function validate( BankData $bankData ): ValidationResult {
		$requiredValidator = new RequiredFieldValidator();
		$stringLengthValidator = new StringLengthValidator();
		$violations = [];

		$violations[] = $this->getFieldViolation(
			$requiredValidator->validate( $bankData->getIban()->toString() ),
			'iban'
		);
		$violations[] = $this->validateBic( $bankData->getBic() );
		$violations[] = $this->getFieldViolation(
			$stringLengthValidator->validate( $bankData->getBankName(), self::BANK_NAME_MAXLEN ),
			'bankname'
		);

		if ( $bankData->getIban()->getCountryCode() === 'DE' ) {
			$violations[] = $this->getFieldViolation(
				$stringLengthValidator->validate( $bankData->getAccount(), self::BANK_ACCOUNT_MAXLEN ),
				'konto'
			);
			$violations[] = $this->getFieldViolation(
				$stringLengthValidator->validate( $bankData->getBankCode(), self::BANK_CODE_MAXLEN ),
				'blz'
			);
		}

		$violations[] = $this->getFieldViolation( $this->ibanValidator->validate( $bankData->getIban() ), 'iban' );

		return new ValidationResult( ...array_filter( $violations ) );
	}

	private function validateBic( string $bic ): ?ConstraintViolation {
		// see https://en.wikipedia.org/wiki/ISO_9362
		if ( $bic === '' || preg_match( '/^[A-Z]{6}[2-9A-Z][0-9A-NP-Z](XXX|[0-9A-WYZ][0-9A-Z]{2})?$/', $bic ) ) {
			return null;
		}
		return new ConstraintViolation( $bic, self::INVALID_BIC, 'bic' );
	}

}
