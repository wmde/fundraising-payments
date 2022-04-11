<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

class FailingIbanValidator implements IbanValidator {
	public function validate( string $value, string $fieldName = '' ): ValidationResult {
		return new ValidationResult(
			new ConstraintViolation(
				$value,
				'IBAN validation failed on purpose in a test'
			)
		);
	}
}
