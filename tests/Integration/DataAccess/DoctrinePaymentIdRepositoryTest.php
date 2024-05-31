<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentId;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

#[CoversClass( DoctrinePaymentIdRepository::class )]
class DoctrinePaymentIdRepositoryTest extends TestCase {

	private EntityManager $entityManager;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
	}

	public function testWhenPaymentIdTableIsEmpty_throwsException(): void {
		$this->expectException( \RuntimeException::class );

		$this->makeRepository()->getNewId();
	}

	public function testWhenGetNextId_getsNextId(): void {
		$this->whenPaymentIdIs( 4 );
		$this->assertEquals( 5, $this->makeRepository()->getNewId() );
	}

	private function makeRepository(): PaymentIdRepository {
		return new DoctrinePaymentIdRepository( $this->entityManager );
	}

	private function whenPaymentIdIs( int $paymentId ): void {
		$this->entityManager->persist( new PaymentId( $paymentId ) );
		$this->entityManager->flush();
	}
}
