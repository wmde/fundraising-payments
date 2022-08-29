<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

/**
 * This service checks with the payment provider if the provided transaction data is valid,
 * e.g. was really sent by the payment provider, and is for the right payment, etc.
 */
interface VerificationService {

	/**
	 * @param array<string,mixed> $transactionData
	 *
	 * @return VerificationResponse
	 */
	public function validate( array $transactionData ): VerificationResponse;
}
