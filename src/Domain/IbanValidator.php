<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ValidationResult;

/**
 * @license GPL-2.0-or-later
 */
interface IbanValidator {

	public function validate( Iban $value, string $fieldName = '' ): ValidationResult;

}
