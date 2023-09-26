<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\UseCases\GenerateIban;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataFailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataSuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData\GenerateBankDataFromGermanLegacyBankDataUseCase;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData\GenerateBankDataFromGermanLegacyBankDataUseCase
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BankDataSuccessResponse
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BankDataFailureResponse
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen <kai.nissen@wikimedia.de>
 */
class GenerateIbanUseCaseTest extends TestCase {

	private BankDataGenerator $bankDataGenerator;
	private IbanBlockList $ibanBlocklist;

	public function setUp(): void {
		$this->bankDataGenerator = $this->newSucceedingBankDataGenerator();
		$this->ibanBlocklist = new IbanBlockList( [] );
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
			->with( '1015754243', '20050550' )
			->willReturn( DirectDebitBankData::validBankData() );

		$useCase = $this->newGenerateIbanUseCase();

		$this->assertEquals(
			new BankDataSuccessResponse( DirectDebitBankData::validBankData() ),
			$useCase->generateIban( '1015754243', '20050550' )
		);
	}

	public function testWhenBankDataGeneratorThrowsException_failureResponseIsReturned(): void {
		$this->bankDataGenerator = $this->createMock( BankDataGenerator::class );
		$this->bankDataGenerator->method( $this->anything() )->willThrowException( new \RuntimeException( 'IBAN is too short' ) );

		$useCase = $this->newGenerateIbanUseCase();
		$response = $useCase->generateIban( '1015754241', '20050550' );

		$this->assertEquals( new BankDataFailureResponse( 'IBAN is too short' ), $response );
	}

	public function testWhenBlockedBankAccountDataIsGiven_failureResponseIsReturned(): void {
		$this->ibanBlocklist = new IbanBlockList( [ DirectDebitBankData::IBAN ] );

		$useCase = $this->newGenerateIbanUseCase();
		$response = $useCase->generateIban( '1194700', '10020500' );

		$this->assertEquals( new BankDataFailureResponse( 'IBAN is blocked' ), $response );
	}
}
