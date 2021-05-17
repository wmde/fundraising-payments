<?php

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DomainException;
use InvalidArgumentException;

interface BookablePayment {
	/**
	 * Mark payment as booked
	 *
	 * Implementations MUST check if $transactionData has the right type.
	 *
	 * @param PaymentTransactionData $transactionData
	 *
	 * @throws DomainException If payment can't be booked (e.g. because it's already booked)
	 * @throws InvalidArgumentException If $transactionData class does not match needed payment type
	 */
	public function bookPayment( PaymentTransactionData $transactionData ): void;
}
