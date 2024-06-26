<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

#[CoversClass( BankTransferPayment::class )]
class BankTransferPaymentTest extends TestCase {
	public function testGivenNewPayment_itReturnsFormattedReferenceCode(): void {
		$payment = $this->makeBankTransferPayment();

		$this->assertSame( 'XW-TAR-ARA-X', $payment->getPaymentReferenceCode() );
	}

	public function testGivenAnonymisedPayment_itReturnsFormattedString(): void {
		$payment = $this->makeBankTransferPayment();
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

	public function testGetLegacyDataForCancelledPayment(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromCents( 7821 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
		$payment->cancel();
		$expectedLegacyData = new LegacyPaymentData(
			7821,
			1,
			'UEB',
			[ 'ueb_code' => 'XW-TAR-ARA-X' ]
		);

		$this->assertEquals( $expectedLegacyData, $payment->getLegacyData() );
	}

	public function testNewPaymentIsNotCancelled(): void {
		$payment = $this->makeBankTransferPayment();

		$this->assertFalse( $payment->isCancelled() );
		$this->assertTrue( $payment->isCancellable() );
	}

	public function testCancelPayment(): void {
		$payment = $this->makeBankTransferPayment();

		$payment->cancel();

		$this->assertTrue( $payment->isCancelled() );
		$this->assertFalse( $payment->isCancellable() );
	}

	public function testRestorePayment(): void {
		$payment = $this->makeCancelledBankTransferPayment();

		$payment->restore();

		$this->assertFalse( $payment->isRestorable() );
		$this->assertFalse( $payment->isCancelled() );
	}

	private function makeBankTransferPayment(): BankTransferPayment {
		return BankTransferPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::Monthly,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
	}

	private function makeCancelledBankTransferPayment(): BankTransferPayment {
		$payment = $this->makeBankTransferPayment();
		$payment->cancel();
		return $payment;
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
			'paymentReferenceCode' => 'XW-TAR-ARA-X'
		];

		$this->assertEquals( $expectedOutput, $payment->getDisplayValues() );
	}

	public function testBankTransferPaymentsAreAlwaysImmediatelyCompletedPayments(): void {
		$payment = $this->makeBankTransferPayment();

		$this->assertTrue( $payment->isCompleted() );
	}
}
