<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\FunValidators\ValidationResponse;

class SucceedingDomainSpecificValidator implements DomainSpecificPaymentValidator {
	public function validatePaymentData( Euro $amount, PaymentInterval $interval ): ValidationResponse {
		return ValidationResponse::newSuccessResponse();
	}

}
