<?php

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

interface BookablePayment {
	/**
	 * Mark payment as booked
	 *
	 * Implementations MUST check if $transactionData has the right shape.
	 *
	 * @param array<string,mixed> $transactionData
	 * @param PaymentIdRepository $idGenerator ID generator in case the booking triggers a followup payment
	 *
	 * @return Payment Might be the same payment instance or a different payment in case of followup payments
	 *
	 * @throws DomainException If payment can't be booked (e.g. because it's already booked)
	 * @throws InvalidArgumentException If $transactionData array does not match needed payment shape
	 */
	public function bookPayment( array $transactionData, PaymentIdRepository $idGenerator ): Payment;

	public function getValuationDate(): ?DateTimeImmutable;

	/**
	 * @param array<string,mixed> $transactionData
	 * @return bool
	 */
	public function canBeBooked( array $transactionData ): bool;
}
