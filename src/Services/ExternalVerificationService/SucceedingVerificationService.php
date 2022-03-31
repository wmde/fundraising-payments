<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService;

use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationService;

class SucceedingVerificationService implements VerificationService {

	public function validate( array $transactionData ): VerificationResponse {
		return VerificationResponse::newSuccessResponse();
	}
}
