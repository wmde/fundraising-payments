<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class DoctrinePaymentIDRepository implements PaymentIDRepository {

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
