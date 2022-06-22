<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * This class and its Doctrine mapping are only used in the test environment to quickly create and
 * tear down the payment_id table. The production environment uses a migration to set up the table.
 *
 * @codeCoverageIgnore
 */
class PaymentId {

	private ?int $id = null;

	public function getId(): ?int {
		return $this->id;
	}
}
