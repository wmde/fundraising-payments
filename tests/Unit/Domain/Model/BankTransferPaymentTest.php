<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment
 */
class BankTransferPaymentTest extends TestCase {
	public function testGivenNewPayment_itReturnsFormattedReferenceCode(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);

		$this->assertSame( 'XW-TAR-ARA-X', $payment->getPaymentReferenceCode() );
	}

	public function testGivenAnonymisedPayment_itReturnsFormattedString(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
		$payment->anonymise();

		$this->assertSame( '', $payment->getPaymentReferenceCode() );
	}

	public function testGetLegacyData(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromCents( 7821 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
		$expectedLegacyData = new LegacyPaymentData(
			7821,
			1,
			'UEB',
			[ 'ueb_code' => 'XW-TAR-ARA-X' ]
		);

		$this->assertEquals( $expectedLegacyData, $payment->getLegacyData() );
	}

	public function testGetDisplayDataReturnsAllFieldsToDisplay(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromCents( 7821 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);

		$expectedOutput = [
			'amount' => 7821,
			'interval' => 1,
			'paymentType' => 'UEB',
			'ueb_code' => 'XW-TAR-ARA-X'
		];

		$this->assertEquals( $expectedOutput, $payment->getDisplayValues() );
	}
}
