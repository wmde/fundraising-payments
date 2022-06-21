<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;

use Doctrine\DBAL\Connection;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;

class DoctrineTransactionIdFinder implements TransactionIdFinder {
	public function __construct( private Connection $db ) {
	}

	public function getAllTransactionIDs( Payment $payment ): array {
		if ( !( $payment instanceof PayPalPayment ) || !$payment->isBooked() ) {
			return [];
		}
		// A small performance shortcut to avoid a database hit
		if ( $payment->getInterval() === PaymentInterval::OneTime ) {
			return [ $payment->getTransactionId() => $payment->getId() ];
		}
		$parent = $payment->getParentPayment();
		$paymentId = $parent === null ? $payment->getId() : $parent->getId();

		$qb = $this->db->createQueryBuilder();
		$qb->select( 'ppl.transaction_id', 'p.id' )
			->from( 'payments', 'p' )
			->join( 'p', 'payments_paypal', 'ppl', 'ppl.id=p.id' )
			->where( 'p.id=:paymentId' )
			->orWhere( 'ppl.parent_payment_id=:paymentId' )
			->setParameter( 'paymentId', $paymentId );

		return $this->convertMixedTypes( $qb->executeQuery()->fetchAllKeyValue() );
	}

	/**
	 * @param array<mixed,mixed> $dbResult
	 * @return array<string,int>
	 */
	private function convertMixedTypes( array $dbResult ): array {
		$transactionIds = [];
		foreach ( $dbResult as $transactionId => $paymentId ) {
			$transactionIds[strval( $transactionId )] = intval( $paymentId );
		}
		return $transactionIds;
	}

	public function transactionIdExists( string $transactionId ): bool {
		$qb = $this->db->createQueryBuilder();
		$qb->select( 'COUNT(id)' )
			->from( 'payments_paypal' )
			->where( 'transaction_id=:transactionId' )
			->setParameter( 'transactionId', $transactionId );
		return $qb->fetchOne() > 0;
	}

}