<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\PaymentReferenceCodeGenerator;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\UniquePaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\IncrementalCharacterIndexGenerator;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\UniquePaymentReferenceCodeGenerator
 */
class UniquePaymentReferenceCodeGeneratorTest extends TestCase {

	private EntityManager $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getFactory()->getEntityManager();
		parent::setUp();
	}

	public function testCodeGeneratorReturnsCode(): void {
		$generator = new UniquePaymentReferenceCodeGenerator(
			new IncrementalCharacterIndexGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-ACD-EFK-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	public function testWhenSofortCodeExistsCodeGeneratorReturnsNextCode(): void {
		$this->insertSofortPayment( 'ACDEFK' );

		$generator = new UniquePaymentReferenceCodeGenerator(
			new IncrementalCharacterIndexGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-LMN-PRT-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	public function testWhenBankTransferCodeExistsCodeGeneratorReturnsNextCode(): void {
		$this->insertBankTransferPayment( 'ACDEFK' );

		$generator = new UniquePaymentReferenceCodeGenerator(
			new IncrementalCharacterIndexGenerator(),
			$this->entityManager
		);

		$this->assertSame( 'AA-LMN-PRT-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
	}

	private function insertSofortPayment( string $code ): void {
		$payment = SofortPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'AA', $code, 'K' )
		);

		$this->entityManager->persist( $payment );
		$this->entityManager->flush();
	}

	private function insertBankTransferPayment( string $code ): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'AA', $code, 'K' )
		);

		$this->entityManager->persist( $payment );
		$this->entityManager->flush();
	}
}
