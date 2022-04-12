<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * This is the value object for {@see LegacyDataTransformer}.
 */
class LegacyPaymentData {
	/**
	 * @param int $amountInEuroCents
	 * @param int $intervalInMonths
	 * @param string $paymentName 3-letter payment name
	 * @param array<string,mixed> $paymentSpecificValues
	 */
	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $intervalInMonths,
		public readonly string $paymentName,
		public readonly array $paymentSpecificValues
	) {
	}
}
