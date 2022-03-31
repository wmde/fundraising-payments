<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

interface VerificationServiceFactory {

	/**
	 * @param Payment $payment
	 *
	 * @return VerificationService
	 */
	public function create( Payment $payment ): VerificationService;
}
