<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class DoctrinePaymentIdRepository implements PaymentIdRepository {

	private EntityManager $entityManager;

	public function __construct( EntityManager $entityManager ) {
		$this->entityManager = $entityManager;
	}

	public function getNewID(): int {
		$connection = $this->entityManager->getConnection();
		$statement = $connection->prepare( 'INSERT INTO payment_ids VALUES ()' );
		$statement->executeStatement();
		return (int)$connection->lastInsertId();
	}
}
