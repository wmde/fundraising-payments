<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use DateTimeImmutable;

class DomainSpecificContext {
	/**
	 * @param int $itemId (internal) donation ID or membership ID
	 * @param string $userAccessToken A token used by our URL generators to grant access to the item.
	 *         It may consist of multiple tokens (access and update token), concatenated with a colon.
	 * @param string $systemAccessToken A token used by our URL generators when creating a URL for server notification
	 *          end point (where an external payment provider confirms a payment)
	 * @param DateTimeImmutable|null $startTimeForRecurringPayment
	 * @param string $invoiceId unique, currently derived from donation/membership ID
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function __construct(
		public readonly int $itemId,
		public readonly string $userAccessToken,
		public readonly string $systemAccessToken,

		public readonly ?DateTimeImmutable $startTimeForRecurringPayment = null,

		public readonly string $invoiceId = '',
		public readonly string $firstName = '',
		public readonly string $lastName = ''
	) {
	}

}
