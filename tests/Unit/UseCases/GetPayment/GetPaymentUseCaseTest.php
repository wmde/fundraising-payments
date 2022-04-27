<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\GetPayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentStatus;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase
 */
class GetPaymentUseCaseTest extends TestCase {

	public function testGivenPaymentId_itReturnsArrayForPayment(): void {
		$testData = [
			"id" => 7777,
			"amount" => Euro::newFromCents( 5000 ),
			"interval" => PaymentInterval::Yearly,
			"iban" => new Iban( 'DE02100500000054540402' ),
			"bic" => 'BELADEBE',
		];
		$testPayment = DirectDebitPayment::create(
			$testData["id"],
			$testData["amount"],
			$testData["interval"],
			$testData["iban"],
			$testData["bic"]
		);
		$useCase = new GetPaymentUseCase( new PaymentRepositorySpy( [ 7 => $testPayment ] ), $this->makeBankDataGeneratorStub() );

		$resultArray = $useCase->getPaymentDataArray( 7 );

		$this->assertSame( $testData["amount"]->getEuroCents(), $resultArray["amount"] );
		$this->assertSame( $testData["interval"]->value, $resultArray["interval"] );
		$this->assertSame( 'BEZ', $resultArray["paymentType"] );
		$this->assertSame( $testData["iban"]->toString(), $resultArray["iban"] );
		$this->assertSame( $testData["bic"], $resultArray["bic"] );
		$this->assertSame( '10050000', $resultArray['blz'] );
		$this->assertSame( '0054540402', $resultArray['konto'] );
		$this->assertEquals( 'Landesbank Berlin', $resultArray['bankname'] );
	}

	public function testGivenPaymentNotFound_ArrayMethodWillThrowException(): void {
		$repo = $this->createStub( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willThrowException( new PaymentNotFoundException() );
		$useCase = new GetPaymentUseCase( $repo, $this->makeBankDataGeneratorDummy() );

		$this->expectException( \DomainException::class );

		$useCase->getPaymentDataArray( 7 );
	}

	public function testGivenAPaymentId_itReturnsLegacyDataForPayment(): void {
		$legacyPaymentData = new LegacyPaymentData(
			1299,
			12,
			'MCP',
			[],
			LegacyPaymentStatus::EXTERNAL_INCOMPLETE->value
		);
		$payment = $this->createStub( CreditCardPayment::class );
		$payment->method( 'getLegacyData' )->willReturn( $legacyPaymentData );
		$useCase = new GetPaymentUseCase( new PaymentRepositorySpy( [ 7 => $payment ] ), $this->makeBankDataGeneratorDummy() );

		$legacyData = $useCase->getLegacyPaymentDataObject( 7 );

		$this->assertSame( $legacyPaymentData, $legacyData );
	}

	public function testGivenPaymentNotFound_LegacyObjectMethodWillThrowException(): void {
		$repo = $this->createStub( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willThrowException( new PaymentNotFoundException() );
		$useCase = new GetPaymentUseCase( $repo, $this->makeBankDataGeneratorDummy() );

		$this->expectException( \DomainException::class );

		$useCase->getLegacyPaymentDataObject( 7 );
	}

	public function testGivenADirectDebitPayment_itLooksUpAdditionalBankData(): void {
		$legacyPaymentData = new LegacyPaymentData(
			1299,
			12,
			'BEZ',
			[ 'iban' => 'DE02100500000054540402' ],
			LegacyPaymentStatus::DIRECT_DEBIT->value
		);
		$payment = $this->createStub( DirectDebitPayment::class );
		$payment->method( 'getLegacyData' )->willReturn( $legacyPaymentData );
		$payment->method( 'getIban' )->willReturn( new Iban( 'DE02100500000054540402' ) );
		$useCase = new GetPaymentUseCase( new PaymentRepositorySpy( [ 7 => $payment ] ), $this->makeBankDataGeneratorStub() );

		$legacyData = $useCase->getLegacyPaymentDataObject( 7 );

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
