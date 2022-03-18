<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ValidationResult;

/**
 * TODO: Move the implementation to this context so this interface is not longer orphaned
 */
interface IbanValidator {

	public function validate( Iban $value, string $fieldName = '' ): ValidationResult;

}
