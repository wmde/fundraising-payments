<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

/**
 * A class for creating a PaymentCreationRequest
 */
class PaymentParameters {

	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $interval,
		public readonly string $paymentType,
		public readonly string $iban = '',
		public readonly string $bic = '',
		public readonly string $transferCodePrefix = ''
	) {
	}

}
