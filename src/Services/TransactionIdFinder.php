<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

/**
 * Return all transaction IDs for a given PayPal payment. If the
 * payment is a recurring payment, return all transaction IDs.
 *
 * For all other payment types and unbooked payments, return an empty array.
 */
interface TransactionIdFinder {
	/**
	 * @param Payment $payment
	 * @return array<string,int> TransactionId to Payment ID
	 */
	public function getAllTransactionIDs( Payment $payment ): array;

	public function transactionIdExists( string $transactionId ): bool;
}
