<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\UseCases\GenerateIban;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData\GenerateBankDataFromGermanLegacyBankDataUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\IbanResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData\GenerateBankDataFromGermanLegacyBankDataUseCase
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen <kai.nissen@wikimedia.de>
 */
class GenerateIbanUseCaseTest extends TestCase {

	private BankDataGenerator $bankDataGenerator;
	private IbanBlocklist $ibanBlocklist;

	public function setUp(): void {
		$this->bankDataGenerator = $this->newSucceedingBankDataGenerator();
		$this->ibanBlocklist = new IbanBlocklist( [] );
	}

	private function newSucceedingBankDataGenerator(): BankDataGenerator {
		$generator = $this->createMock( BankDataGenerator::class );

		$generator->method( $this->anything() )->willReturn( DirectDebitBankData::validBankData() );

		return $generator;
	}

	private function newGenerateIbanUseCase(): GenerateBankDataFromGermanLegacyBankDataUseCase {
		return new GenerateBankDataFromGermanLegacyBankDataUseCase(
			$this->bankDataGenerator,
			$this->ibanBlocklist
		);
	}

	public function testWhenValidBankAccountDataIsGiven_fullBankDataIsReturned(): void {
		$this->bankDataGenerator = $this->createMock( BankDataGenerator::class );

		$this->bankDataGenerator->expects( $this->once() )
			->method( 'getBankDataFromAccountData' )
			->with( $this->equalTo( '1015754243' ), $this->equalTo( '20050550' ) )
			->willReturn( DirectDebitBankData::validBankData() );

		$useCase = $this->newGenerateIbanUseCase();

		$this->assertEquals(
			IbanResponse::newSuccessResponse( DirectDebitBankData::validBankData() ),
			$useCase->generateIban( '1015754243', '20050550' )
		);
	}

	public function testWhenBankDataGeneratorThrowsException_failureResponseIsReturned(): void {
		$this->bankDataGenerator = $this->createMock( BankDataGenerator::class );
		$this->bankDataGenerator->method( $this->anything() )->willThrowException( new \RuntimeException() );

		$useCase = $this->newGenerateIbanUseCase();
		$response = $useCase->generateIban( '1015754241', '20050550' );

		$this->assertFalse( $response->isSuccessful() );
	}

	public function testWhenBlockedBankAccountDataIsGiven_failureResponseIsReturned(): void {
		$this->ibanBlocklist = new IbanBlocklist( [ DirectDebitBankData::IBAN ] );

		$useCase = $this->newGenerateIbanUseCase();
		$response = $useCase->generateIban( '1194700', '10020500' );

		$this->assertFalse( $response->isSuccessful() );
	}
}
