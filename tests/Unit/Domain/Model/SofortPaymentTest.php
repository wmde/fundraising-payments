<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\ValuationDateTimeZone;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;

#[CoversClass( SofortPayment::class )]
class SofortPaymentTest extends TestCase {

	public function testGetPaymentCode(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->assertEquals( 'XW-DAR-E99-X', $sofortPayment->getPaymentReferenceCode() );
	}

	public function testGetPaymentCodeOfAnonymisedPaymentsReturnsEmptyString(): void {
		$sofortPayment = $this->makeSofortPayment();
		$sofortPayment->anonymise();

		$this->assertSame( '', $sofortPayment->getPaymentReferenceCode() );
	}

	public function testNewSofortPaymentsAreUnbookedAndIncomplete(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->assertTrue( $sofortPayment->canBeBooked( $this->makeValidTransactionData() ) );
		$this->assertFalse( $sofortPayment->isCompleted() );
	}

	public function testGivenNonOneTimePaymentIntervalThrowsException(): void {
		$this->expectException( InvalidArgumentException::class );

		SofortPayment::create( 1, Euro::newFromCents( 1000 ), PaymentInterval::HalfYearly, new PaymentReferenceCode( 'XW', 'DARE99', 'X' ) );
	}

	public function testBookPaymentSetsCompleted(): void {
		$sofortPayment = $this->makeSofortPayment();

		$sofortPayment->bookPayment( $this->makeValidTransactionData(), new DummyPaymentIdRepository() );

		$this->assertTrue( $sofortPayment->isCompleted() );
		$this->assertFalse( $sofortPayment->canBeBooked( $this->makeValidTransactionData() ) );
	}

	public function testBookPaymentValidatesDate(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->expectException( DomainException::class );
		$this->expectExceptionMessageMatches( '/Error in valuation date/' );

		$sofortPayment->bookPayment( [ 'transactionId' => 'yellow', 'valuationDate' => '2001-12-24' ], new DummyPaymentIdRepository() );
	}

	public function testBookPaymentValidatesTransactionIdNotEmpty(): void {
		$sofortPayment = $this->makeSofortPayment();

		$this->expectException( DomainException::class );
		$this->expectExceptionMessageMatches( '/Transaction ID missing/' );

		$sofortPayment->bookPayment( [ 'transactionId' => '', 'valuationDate' => '2001-12-24T17:30:00Z' ], new DummyPaymentIdRepository() );
	}

	public function testBookPaymentSetsValuationDate(): void {
		$sofortPayment = $this->makeSofortPayment();

		$sofortPayment->bookPayment( $this->makeValidTransactionData(), new DummyPaymentIdRepository() );

		$this->assertEquals(
			new DateTimeImmutable( '2001-12-24T17:30:00', ValuationDateTimeZone::getTimeZone() ),
			$sofortPayment->getValuationDate()
		);
	}

	public function testPaymentCannotBeBookedTwice(): void {
		$sofortPayment = $this->makeSofortPayment();
		$sofortPayment->bookPayment( $this->makeValidTransactionData(), new DummyPaymentIdRepository() );

		$this->expectException( DomainException::class );

		$sofortPayment->bookPayment( $this->makeValidTransactionData(), new DummyPaymentIdRepository() );
	}

	public function testNewPaymentHasFormattedReferenceCodeInLegacyData(): void {
		$sofortPayment = $this->makeSofortPayment();

		$legacyData = $sofortPayment->getLegacyData();

		$this->assertSame( [ 'ueb_code' => 'XW-DAR-E99-X' ], $legacyData->paymentSpecificValues );
	}

	public function testAnonymisedPaymentHasEmptyReferenceCodeInLegacyData(): void {
		$sofortPayment = $this->makeSofortPayment();
		$sofortPayment->anonymise();

		$legacyData = $sofortPayment->getLegacyData();

		$this->assertSame( [ 'ueb_code' => '' ], $legacyData->paymentSpecificValues );
	}

	public function testBookedPaymentHasTransactionDataInLegacyData(): void {
		$sofortPayment = $this->makeSofortPayment();
		$sofortPayment->bookPayment( $this->makeValidTransactionData(), new DummyPaymentIdRepository() );
		$expectedLegacyData = [
			'ueb_code' => 'XW-DAR-E99-X',
			'transaction_id' => 'yellow',
			'valuation_date' => '2001-12-24 17:30:00'
		];

		$legacyData = $sofortPayment->getLegacyData();

		$this->assertEquals( $expectedLegacyData, $legacyData->paymentSpecificValues );
	}

	public function testGetDisplayDataReturnsAllFieldsToDisplay(): void {
		$payment = $this->makeSofortPayment();
		$payment->bookPayment( $this->makeValidTransactionData(), new DummyPaymentIdRepository() );

		$expectedOutput = [
			'amount' => 1000,
			'interval' => 0,
			'paymentType' => 'SUB',
			'paymentReferenceCode' => 'XW-DAR-E99-X',
			'transactionId' => 'yellow',
			'valuationDate' => '2001-12-24 17:30:00'
		];

		$this->assertNotNull( $payment->getValuationDate() );
		$this->assertFalse( $payment->canBeBooked( [] ) );
		$this->assertEquals( $expectedOutput, $payment->getDisplayValues() );
	}

	private function makeSofortPayment(): SofortPayment {
		return SofortPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'DARE99', 'X' )
		);
	}

	/**
	 * @return array<string,string>
	 */
	private function makeValidTransactionData(): array {
		return [ 'transactionId' => 'yellow', 'valuationDate' => '2001-12-24T17:30:00Z' ];
	}
}
