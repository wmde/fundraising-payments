<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\KontoCheck;

use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

class KontoCheckIbanValidator implements IbanValidator {

	/**
	 * Mirrors the "OK" constant defined by the kontocheck extension
	 */
	private const KONTOCHECK_OK = 1;

	public function __construct() {
		$initializationResult = lut_init();
		if ( $initializationResult !== self::KONTOCHECK_OK ) {
			throw new KontoCheckLibraryInitializationException( null, $initializationResult );
		}
	}

	public function validate( string $iban, string $fieldName = '' ): ValidationResult {
		if ( iban_check( $iban ) <= 0 ) {
			return new ValidationResult( new ConstraintViolation( $iban, 'iban_invalid', $fieldName ) );
		}

		return new ValidationResult();
	}
}
