<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\UrlGenerator;

use DateTimeImmutable;

/**
 * Bounded contexts can pass in information that is specific to their domain,
 * but needed for certain payment services and use cases.
 */
class DomainSpecificContext {
	/**
	 * @param int $itemId (internal) donation ID or membership ID
	 * @param DateTimeImmutable|null $startTimeForRecurringPayment
	 * @param string $invoiceId unique, currently derived from donation/membership ID
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function __construct(
		public readonly int $itemId,
		public readonly ?DateTimeImmutable $startTimeForRecurringPayment = null,
		public readonly string $invoiceId = '',
		public readonly string $firstName = '',
		public readonly string $lastName = ''
	) {
	}

	public function getRequestContextForUrlGenerator(): DomainSpecificContext {
		return new DomainSpecificContext(
			$this->itemId,
			null,
			$this->invoiceId,
			$this->firstName,
			$this->lastName
		);
	}
}
