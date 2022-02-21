<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentIDRepository
 */
class DoctrinePaymentIDRepositoryTest extends TestCase {

	private EntityManager $entityManager;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
		parent::setUp();
	}

	public function testWhenGetNextID_getsNextID(): void {
		$this->whenPaymentIDCountIs( 4 );
		$this->assertEquals( 5, $this->makeRepository()->getNewID() );
	}

	private function makeRepository(): PaymentIDRepository {
		return new DoctrinePaymentIDRepository( $this->entityManager );
	}

	private function whenPaymentIDCountIs( int $count ): void {
		$connection = $this->entityManager->getConnection();
		for ( $i = 0; $i < $count; $i++ ) {
			$statement = $connection->prepare( 'INSERT INTO payment_ids DEFAULT VALUES' );
			$statement->executeStatement();
		}
	}
}
