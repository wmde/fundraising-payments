<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\FunValidators\ValidationResult;

class SucceedingIbanValidator implements IbanValidator {
	public function validate( string $value, string $fieldName = '' ): ValidationResult {
		return new ValidationResult();
	}

}
