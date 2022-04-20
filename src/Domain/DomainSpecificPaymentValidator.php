<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\FunValidators\ValidationResponse;

interface DomainSpecificPaymentValidator {
	public function validatePaymentData( Euro $amount, PaymentInterval $interval ): ValidationResponse;
}
