<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\FunValidators\ValidationResult;

interface IbanValidator {

	public function validate( string $value, string $fieldName = '' ): ValidationResult;

}
