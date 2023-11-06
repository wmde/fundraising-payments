<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\ScalarTypeConverter;
use WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;

class DoctrineTransactionIdFinder implements TransactionIdFinder {
	public function __construct( private Connection $db ) {
	}

	public function getAllTransactionIDs( Payment $payment ): array {
		if ( !( $payment instanceof PayPalPayment ) || !$payment->isBooked() ) {
			return [];
		}
		// A small performance shortcut to avoid a database hit
		if ( !$payment->getInterval()->isRecurring() ) {
			return [ $payment->getTransactionId() => $payment->getId() ];
		}
		$parent = $payment->getParentPayment();
		$paymentId = $parent === null ? $payment->getId() : $parent->getId();

		// TODO check if the JOIN is really necessary
		$qb = $this->db->createQueryBuilder();
		$qb->select( 'ppl.transaction_id', 'p.id' )
			->from( 'payment', 'p' )
			->join( 'p', 'payment_paypal', 'ppl', 'ppl.id=p.id' )
			->where( 'p.id=:paymentId' )
			->orWhere( 'ppl.parent_payment_id=:paymentId' )
			->setParameter( 'paymentId', $paymentId, ParameterType::INTEGER );

		return $this->convertMixedTypes( $qb->executeQuery()->fetchAllKeyValue() );
	}

	/**
	 * @param array<mixed,mixed> $dbResult
	 * @return array<string,int>
	 */
	private function convertMixedTypes( array $dbResult ): array {
		$transactionIds = [];
		foreach ( $dbResult as $transactionId => $paymentId ) {
			$transactionIds[ScalarTypeConverter::toString( $transactionId )] = ScalarTypeConverter::toInt( $paymentId );
		}
		return $transactionIds;
	}

	public function transactionIdExists( string $transactionId ): bool {
		$qb = $this->db->createQueryBuilder();
		$qb->select( 'COUNT(id)' )
			->from( 'payment_paypal' )
			->where( 'transaction_id=:transactionId' )
			->setParameter( 'transactionId', $transactionId );
		return $qb->fetchOne() > 0;
	}

}
