<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Services\LegacyDataProvider;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\LegacyDataProvider
 */
class LegacyDataProviderTest extends TestCase {
	public function testGivenAPaymentId_itReturnsLegacyDataForPayment(): void {
		$legacyPaymentData = new LegacyPaymentData( 1299, 12, 'MCP', [] );
		$payment = $this->createStub( CreditCardPayment::class );
		$payment->method( 'getLegacyData' )->willReturn( $legacyPaymentData );
		$provider = new LegacyDataProvider( new PaymentRepositorySpy( [ 7 => $payment ] ), $this->makeBankDataGeneratorDummy() );

		$legacyData = $provider->getDataForPayment( 7 );

		$this->assertSame( $legacyPaymentData, $legacyData );
	}

	public function testGivenPaymentNotFound_willThrowException(): void {
		$repo = $this->createStub( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willThrowException( new PaymentNotFoundException() );
		$provider = new LegacyDataProvider( $repo, $this->makeBankDataGeneratorDummy() );

		$this->expectException( \DomainException::class );

		$provider->getDataForPayment( 7 );
	}

	public function testGivenADirectDebitPayment_itLooksUpAdditionalBankData(): void {
		$legacyPaymentData = new LegacyPaymentData( 1299, 12, 'BEZ', [ 'iban' => 'DE02100500000054540402' ] );
		$payment = $this->createStub( DirectDebitPayment::class );
		$payment->method( 'getLegacyData' )->willReturn( $legacyPaymentData );
		$payment->method( 'getIban' )->willReturn( new Iban( 'DE02100500000054540402' ) );
		$provider = new LegacyDataProvider( new PaymentRepositorySpy( [ 7 => $payment ] ), $this->makeBankDataGeneratorStub() );

		$legacyData = $provider->getDataForPayment( 7 );

		$this->assertNotSame( $legacyPaymentData, $legacyData );
		$this->assertSame( 'DE02100500000054540402', $legacyData->paymentSpecificValues['iban'] );
		$this->assertSame( '10050000', $legacyData->paymentSpecificValues['blz'] );
		$this->assertSame( '0054540402', $legacyData->paymentSpecificValues['konto'] );
		$this->assertEquals( 'Landesbank Berlin', $legacyData->paymentSpecificValues['bankname'] );
		$this->assertEquals( 'BELADEBE', $legacyData->paymentSpecificValues['bic'] );
	}

	private function makeBankDataGeneratorStub(): BankDataGenerator {
		$generator = $this->createStub( BankDataGenerator::class );
		$generator->method( 'getBankDataFromIban' )
			->willReturn( new ExtendedBankData(
				new Iban( 'DE02100500000054540402' ),
					'BELADEBE',
					'0054540402',
					'10050000',
					'Landesbank Berlin'
			) );
		return $generator;
	}

	private function makeBankDataGeneratorDummy(): BankDataGenerator {
		$generator = $this->createStub( BankDataGenerator::class );
		$generator->method( 'getBankDataFromIban' )
			->willThrowException( new \LogicException( 'Code should not call this' ) );
		return $generator;
	}
}
