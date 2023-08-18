<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use DateTimeImmutable;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;

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

	public function getRequestContextForUrlGenerator(): RequestContext {
		return new RequestContext(
			$this->itemId,
			$this->invoiceId,
			$this->firstName,
			$this->lastName
		);
	}
}
