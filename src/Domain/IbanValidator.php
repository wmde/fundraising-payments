<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\FunValidators\ValidationResult;

interface IbanValidator {

	public function validate( string $iban, string $fieldName = '' ): ValidationResult;

}
