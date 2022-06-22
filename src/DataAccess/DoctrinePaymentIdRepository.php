<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class DoctrinePaymentIdRepository implements PaymentIdRepository {

	private EntityManager $entityManager;

	public function __construct( EntityManager $entityManager ) {
		$this->entityManager = $entityManager;
	}

	public function getNewId(): int {
		$connection = $this->entityManager->getConnection();

		$paymentId = $connection->transactional( function ( Connection $connection ): int {
			$this->updatePaymentId( $connection );
			$result = $this->getCurrentIdResult( $connection );
			$id = $result->fetchOne();

			if ( $id === false ) {
				throw new \RuntimeException( 'The ID generator needs a row with initial payment_id set to 0.' );
			}

			return intval( $id );
		} );

		return intval( $paymentId );
	}

	private function updatePaymentId( Connection $connection ): void {
		$statement = $connection->prepare( "UPDATE payment_id SET payment_id = payment_id + 1" );
		$statement->executeStatement();
	}

	private function getCurrentIdResult( Connection $connection ): Result {
		$statement = $connection->prepare( 'SELECT payment_id FROM payment_id LIMIT 1' );
		return $statement->executeQuery();
	}
}
