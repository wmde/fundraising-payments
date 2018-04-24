<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\UseCases\GenerateIban;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\ResponseModel\IbanResponse;
use WMDE\Fundraising\PaymentContext\Tests\Data\ValidBankData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FailingIbanBlacklist;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingIbanBlacklist;
use WMDE\Fundraising\PaymentContext\UseCases\GenerateIban\GenerateIbanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\GenerateIban\GenerateIbanUseCase;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\GenerateIban\GenerateIbanUseCase
 *
 * @licence GNU GPL v2+
 * @author Kai Nissen <kai.nissen@wikimedia.de>
 */
class GenerateIbanUseCaseTest extends TestCase {

	private $bankDataGenerator;
	private $ibanBlacklist;

	public function setUp() {
		$this->bankDataGenerator = $this->newSucceedingBankDataGenerator();
		$this->ibanBlacklist = new SucceedingIbanBlacklist();
	}

	private function newSucceedingBankDataGenerator(): BankDataGenerator {
		$generator = $this->createMock( BankDataGenerator::class );

		$generator->method( $this->anything() )->willReturn( new ValidBankData() );

		return $generator;
	}

	private function newGenerateIbanUseCase(): GenerateIbanUseCase {
		return new GenerateIbanUseCase(
			$this->bankDataGenerator,
			$this->ibanBlacklist
		);
	}

	public function testWhenValidBankAccountDataIsGiven_fullBankDataIsReturned(): void {
		$this->bankDataGenerator = $this->createMock( BankDataGenerator::class );

		$this->bankDataGenerator->expects( $this->once() )
			->method( 'getBankDataFromAccountData' )
			->with( $this->equalTo( '1015754243' ), $this->equalTo( '20050550' ) )
			->willReturn( new ValidBankData() );

		$useCase = $this->newGenerateIbanUseCase();

		$this->assertEquals(
			IbanResponse::newSuccessResponse( new ValidBankData() ),
			$useCase->generateIban( new GenerateIbanRequest( '1015754243', '20050550' ) )
		);
	}

	public function testWhenBankDataGeneratorThrowsException_failureResponseIsReturned(): void {
		$this->bankDataGenerator = $this->createMock( BankDataGenerator::class );
		$this->bankDataGenerator->method( $this->anything() )->willThrowException( new \RuntimeException() );

		$useCase = $this->newGenerateIbanUseCase();

		$this->assertEquals(
			IbanResponse::newFailureResponse(),
			$useCase->generateIban( new GenerateIbanRequest( '1015754241', '20050550' ) )
		);
	}

	public function testWhenBlockedBankAccountDataIsGiven_failureResponseIsReturned(): void {
		$this->ibanBlacklist = new FailingIbanBlacklist();

		$useCase = $this->newGenerateIbanUseCase();

		$this->assertEquals(
			IbanResponse::newFailureResponse(),
			$useCase->generateIban( new GenerateIbanRequest( '1194700', '10020500' ) )
		);
	}
}
