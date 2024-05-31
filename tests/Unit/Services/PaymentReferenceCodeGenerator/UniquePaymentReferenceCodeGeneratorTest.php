<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PaymentReferenceCodeGenerator;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\UniquePaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FixedPaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

#[CoversClass( UniquePaymentReferenceCodeGenerator::class )]
class UniquePaymentReferenceCodeGeneratorTest extends TestCase {

	private EntityManager $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getFactory()->getEntityManager();
		parent::setUp();
	}

	public function testCodeGeneratorReturnsCode(): void {
		$generator = new UniquePaymentReferenceCodeGenerator(
			$this->makeIncrementalPaymentReferenceCodeGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-ACD-EFK-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	public function testWhenSofortCodeExistsCodeGeneratorReturnsNextCode(): void {
		$this->insertSofortPayment( 'ACDEFK' );

		$generator = new UniquePaymentReferenceCodeGenerator(
			$this->makeIncrementalPaymentReferenceCodeGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-LMN-PRT-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	public function testWhenBankTransferCodeExistsCodeGeneratorReturnsNextCode(): void {
		$this->insertBankTransferPayment( 'ACDEFK' );

		$generator = new UniquePaymentReferenceCodeGenerator(
			$this->makeIncrementalPaymentReferenceCodeGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-LMN-PRT-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	public function testWhenSofortAndBankTransferCodeExistsCodeGeneratorReturnsNextCode(): void {
		$this->insertSofortPayment( 'ACDEFK' );
		$this->insertBankTransferPayment( 'LMNPRT', 2 );

		$generator = new UniquePaymentReferenceCodeGenerator(
			$this->makeIncrementalPaymentReferenceCodeGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-WXY-Z34-9', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	private function makeIncrementalPaymentReferenceCodeGenerator(): PaymentReferenceCodeGenerator {
		return new FixedPaymentReferenceCodeGenerator( [
			new PaymentReferenceCode( 'AA', 'ACDEFK', 'K' ),
			new PaymentReferenceCode( 'AA', 'LMNPRT', 'K' ),
			new PaymentReferenceCode( 'AA', 'WXYZ34', '9' ),
		] );
	}

	private function insertSofortPayment( string $code, int $id = 1 ): void {
		$payment = SofortPayment::create(
			$id,
			Euro::newFromCents( 1000 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'AA', $code, 'K' )
		);

		$this->entityManager->persist( $payment );
		$this->entityManager->flush();
	}

	private function insertBankTransferPayment( string $code, int $id = 1 ): void {
		$payment = BankTransferPayment::create(
			$id,
			Euro::newFromCents( 1000 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'AA', $code, 'K' )
		);

		$this->entityManager->persist( $payment );
		$this->entityManager->flush();
	}
}
