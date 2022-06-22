<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\Tests\TestPaymentContextFactory;

/**
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentIdRepository
 */
class DoctrinePaymentIdRepositoryTest extends TestCase {

	private TestPaymentContextFactory $factory;
	private EntityManager $entityManager;

	public function setUp(): void {
		$this->factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $this->factory->getEntityManager();
		parent::setUp();
	}

	public function testWhenGetNextID_getsNextID(): void {
		$this->whenPaymentIDCountIs( 4 );
		$this->assertEquals( 5, $this->makeRepository()->getNewID() );
	}

	public function testDoctrineReturnsCorrectLastInsertIDPerConnection(): void {
		$connection1 = $this->makeConnection();
		$connection2 = $this->makeConnection();
		$connection3 = $this->makeConnection();

		$this->insertPaymentID( $connection1 );
		$this->insertPaymentID( $connection3 );
		$this->insertPaymentID( $connection2 );

		$this->assertSame( '1', $connection1->lastInsertId() );
		$this->assertSame( '3', $connection2->lastInsertId() );
		$this->assertSame( '2', $connection3->lastInsertId() );
	}

	private function makeRepository(): PaymentIDRepository {
		return new DoctrinePaymentIdRepository( $this->entityManager );
	}

	private function whenPaymentIDCountIs( int $count ): void {
		$connection = $this->entityManager->getConnection();
		for ( $i = 0; $i < $count; $i++ ) {
			$this->insertPaymentID( $connection );
		}
	}

	private function makeConnection(): Connection {
		return $this->factory->newEntityManager()->getConnection();
	}

	private function insertPaymentID( Connection $connection ): void {
		$statement = $connection->prepare( 'INSERT INTO payment_ids VALUES ()' );
		$statement->executeStatement();
	}
}
