<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * This is the return value object for {@see LegacyDataTransformer}.
 *
 * When https://phabricator.wikimedia.org/T320781 (removing legacy data reading from FOC)
 * and https://phabricator.wikimedia.org/T359941 (removing legacy data writing from donations and memberships)
 * are done, we can delete this class and all methods that reference it.
 *
 * Since these tickets might take a long time, we don't add a "@deprecated" tag yet.
 */
class LegacyPaymentData {

	/**
	 * @param int $amountInEuroCents
	 * @param int $intervalInMonths
	 * @param string $paymentName 3-letter payment name, {@see PaymentType} values
	 * @param array<string,scalar> $paymentSpecificValues Data that will be stored in the "data blob" of donations.
	 * @param string $paymentStatus Deprecated status ({@see LegacyPaymentStatus}) it's not used anywhere in bounded contexts
	 */
	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $intervalInMonths,
		public readonly string $paymentName,
		public readonly array $paymentSpecificValues,
		public readonly string $paymentStatus
	) {
	}
}
