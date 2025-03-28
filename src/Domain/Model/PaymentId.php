<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * This class and its Doctrine mapping are only used in the test environment to quickly create and
 * tear down the last_generated_payment_id table. The production environment uses a migration to set up the table.
 *
 * When setting up a test environment that needs to generate payment IDs in the database,
 * you must insert one PaymentId into the table. The easiest way to accomplish this is to run
 *
 * ```php
 * $entityManager->persist( new PaymentId() );
 * $entityManager->flush();
 * ```
 *
 * @codeCoverageIgnore
 */
class PaymentId {

	/**
	 * used for doctrine mapping only
	 * @phpstan-ignore-next-line
	 */
	private ?int $id = null;
	private int $paymentId;

	public function __construct( int $paymentId = 0 ) {
		$this->paymentId = $paymentId;
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function getPaymentId(): int {
		return $this->paymentId;
	}
}
