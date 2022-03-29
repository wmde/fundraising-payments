<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\SofortPaymentInspector;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment
 */
class SofortPaymentTest extends TestCase {

	public function testGetPaymentCode(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->assertEquals( 'XW-DAR-E99-X', $sofortPayment->getPaymentReferenceCode() );
	}

	public function testNewSofortPaymentsAreUncompleted(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->assertFalse( $sofortPayment->paymentCompleted() );
	}

	public function testGivenNonOneTimePaymentIntervalThrowsException(): void {
		$this->expectException( \InvalidArgumentException::class );

		SofortPayment::create( 1, Euro::newFromCents( 1000 ), PaymentInterval::HalfYearly, new PaymentReferenceCode( 'XW', 'DARE99', 'X' ) );
	}

	public function testBookPaymentSetsCompleted(): void {
		$sofortPayment = $this->makeSofortPayment();

		$sofortPayment->bookPayment( [ 'transactionId' => 'yellow', 'valuationDate' => '2001-12-24T17:30:00Z' ] );

		$this->assertTrue( $sofortPayment->paymentCompleted() );
	}

	public function testBookPaymentValidatesDate(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->expectException( \DomainException::class );
		$this->expectExceptionMessageMatches( '/Error in valuation date/' );

		$sofortPayment->bookPayment( [ 'transactionId' => 'yellow', 'valuationDate' => '2001-12-24' ] );
	}

	public function testBookPaymentValidatesTransactionIdNotEmpty(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->expectException( \DomainException::class );
		$this->expectExceptionMessageMatches( '/Transaction ID missing/' );

		$sofortPayment->bookPayment( [ 'transactionId' => '', 'valuationDate' => '2001-12-24T17:30:00Z' ] );
	}

	public function testBookPaymentSetsValuationDate(): void {
		$sofortPayment = $this->makeSofortPayment();

		$sofortPayment->bookPayment( [ 'transactionId' => 'yellow', 'valuationDate' => '2001-12-24T17:30:00Z' ] );

		$this->assertEquals( new \DateTimeImmutable( '2001-12-24T17:30:00Z' ), $sofortPayment->getValuationDate() );
	}

	public function testBookPaymentSetsTransactionId(): void {
		$sofortPayment = $this->makeSofortPayment();

		$sofortPayment->bookPayment( [ 'transactionId' => 'yellow', 'valuationDate' => '2001-12-24T17:30:00Z' ] );

		$sofortPaymentInspector = new SofortPaymentInspector( $sofortPayment );

		$this->assertEquals( 'yellow', $sofortPaymentInspector->getTransactionId() );
	}

	private function makeSofortPayment(): SofortPayment {
		return SofortPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'DARE99', 'X' )
		);
	}
}
