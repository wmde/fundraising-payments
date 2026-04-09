<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\DatabasePaymentAnonymizer;
use WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\AnonymizationException;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

#[CoversClass( DatabasePaymentAnonymizer::class )]
class DatabasePaymentAnonymizerTest extends TestCase {

	private const string IBAN = 'DE00123456789012345678';
	private const string BIC = 'SCROUSDBXXX';

	private EntityManager $entityManager;
	private PaymentRepository $paymentRepository;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
		$this->paymentRepository = new DoctrinePaymentRepository(
			$this->entityManager
		);
	}

	public function testAnonymisesDirectDebitPayments(): void {
		$this->insertExamplePayments();

		$anonymizer = new DatabasePaymentAnonymizer( $this->paymentRepository, $this->entityManager );

		$anonymizer->anonymizeWithIds( ...[ 1, 2 ] );

		$payment1 = $this->paymentRepository->getPaymentById( 1 );
		$payment2 = $this->paymentRepository->getPaymentById( 2 );
		$payment3 = $this->paymentRepository->getPaymentById( 3 );

		$this->assertSame( "", $payment1->getDisplayValues()[ 'iban' ] );
		$this->assertSame( "", $payment1->getDisplayValues()[ 'bic' ] );
		$this->assertSame( "", $payment2->getDisplayValues()[ 'iban' ] );
		$this->assertSame( "", $payment2->getDisplayValues()[ 'bic' ] );
		$this->assertSame( self::IBAN, $payment3->getDisplayValues()[ 'iban' ] );
		$this->assertSame( self::BIC, $payment3->getDisplayValues()[ 'bic' ] );
	}

	public function testThrowsWhenCantFindPayment(): void {
		$this->insertExamplePayments();

		$anonymizer = new DatabasePaymentAnonymizer( $this->paymentRepository, $this->entityManager );

		$this->expectException( AnonymizationException::class );
		$this->expectExceptionMessageMatches( "/Payment with id 4 not found/" );

		$anonymizer->anonymizeWithIds( 4 );
	}

	private function insertExamplePayments(): void {
		$this->entityManager->persist( DirectDebitPayment::create( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly, new Iban( self::IBAN ), self::BIC ) );
		$this->entityManager->persist( DirectDebitPayment::create( 2, Euro::newFromInt( 26 ), PaymentInterval::Quarterly, new Iban( self::IBAN ), self::BIC ) );
		$this->entityManager->persist( DirectDebitPayment::create( 3, Euro::newFromInt( 42 ), PaymentInterval::Quarterly, new Iban( self::IBAN ), self::BIC ) );

		$this->entityManager->flush();
	}
}
