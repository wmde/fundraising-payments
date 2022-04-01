<?php

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

interface BookablePayment {
	/**
	 * Mark payment as booked
	 *
	 * Implementations MUST check if $transactionData has the right shape.
	 *
	 * @param array<string,mixed> $transactionData
	 *
	 * @throws DomainException If payment can't be booked (e.g. because it's already booked)
	 * @throws InvalidArgumentException If $transactionData array does not match needed payment shape
	 */
	public function bookPayment( array $transactionData ): void;

	public function getValuationDate(): ?DateTimeImmutable;

	/**
	 * @param array<string,mixed> $transactionData
	 * @return bool
	 */
	public function canBeBooked( array $transactionData ): bool;
}
