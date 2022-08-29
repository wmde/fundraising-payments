<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;

class FakeTransactionIdFinder implements TransactionIdFinder {

	/**
	 * @param array<string,int> $transactionIds
	 */
	public function __construct( private readonly array $transactionIds = [] ) {
	}

	public function getAllTransactionIDs( Payment $payment ): array {
		return $this->transactionIds;
	}

	public function transactionIdExists( string $transactionId ): bool {
		return !empty( $this->transactionIds[$transactionId] );
	}

}
