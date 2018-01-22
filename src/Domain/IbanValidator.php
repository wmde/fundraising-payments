<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ValidationResult;

/**
 * @licence GNU GPL v2+
 */
interface IbanValidator {

	public function validate( Iban $value, string $fieldName = '' ): ValidationResult;

	public function isIbanBlocked( Iban $iban ): bool;

}
