<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment
 */
class DirectDebitPaymentTest extends TestCase {
	private const PAYMENT_ID = 78;

	public function testNewPaymentIsNotCancelled(): void {
		$payment = $this->makeDirectDebitPayment();

		$this->assertFalse( $payment->isCancelled() );
		$this->assertTrue( $payment->isCancellable() );
	}

	public function testCancelPayment(): void {
		$payment = $this->makeDirectDebitPayment();

		$payment->cancel();

		$this->assertTrue( $payment->isCancelled() );
		$this->assertFalse( $payment->isCancellable() );
	}

	public function testNewPaymentCanReturnIbanAndBic(): void {
		$iban = new Iban( DirectDebitBankData::IBAN );
		$payment = DirectDebitPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 4999 ),
			PaymentInterval::Quarterly,
			$iban,
			DirectDebitBankData::BIC
		);

		$this->assertEquals( $iban, $payment->getIban() );
		$this->assertSame( DirectDebitBankData::BIC, $payment->getBic() );
	}

	public function testAnonymisedPaymentHasNoIbanAndBic(): void {
		$payment = $this->makeDirectDebitPayment();

		$payment->anonymise();

		$this->assertNull( $payment->getIban() );
		$this->assertNull( $payment->getBic() );
	}

	public function testPaymentReturnsIbanAndBicInLegacyData(): void {
		$payment = $this->makeDirectDebitPayment();
		$expectedAdditionalData = [
			'iban' => DirectDebitBankData::IBAN,
			'bic' => DirectDebitBankData::BIC
		];

		$legacyData = $payment->getLegacyData();

		$this->assertSame( $expectedAdditionalData, $legacyData->paymentSpecificValues );
	}

	public function testAnonymisedPaymentReturnsEmptyIbanAndBicInLegacyData(): void {
		$payment = $this->makeDirectDebitPayment();
		$payment->anonymise();

		$legacyData = $payment->getLegacyData();

		$this->assertSame( [ 'iban' => '', 'bic' => '' ], $legacyData->paymentSpecificValues );
	}

	public function testGetDisplayDataReturnsAllFieldsToDisplay(): void {
		$payment = $this->makeDirectDebitPayment();

		$expectedOutput = [
			'amount' => 4999,
			'interval' => 3,
			'paymentType' => 'BEZ',
			'iban' => 'DE00123456789012345678',
			'bic' => 'SCROUSDBXXX'
		];

		$this->assertEquals( $expectedOutput, $payment->getDisplayValues() );
	}

	private function makeDirectDebitPayment(): DirectDebitPayment {
		return DirectDebitPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 4999 ),
			PaymentInterval::Quarterly,
			new Iban( DirectDebitBankData::IBAN ),
			DirectDebitBankData::BIC
		);
	}
}
