<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentTypes;
use WMDE\FunValidators\ValidationResponse;

class SucceedingDomainSpecificValidator implements DomainSpecificPaymentValidator {
	public function validatePaymentData( Euro $amount, PaymentInterval $interval, PaymentTypes $paymentType ): ValidationResponse {
		return ValidationResponse::newSuccessResponse();
	}

}
