<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\UseCases\CheckIban;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Tests\Data\ValidBankData;
use WMDE\Fundraising\PaymentContext\UseCases\CheckIban\CheckIbanUseCase;
use PHPUnit\Framework\TestCase;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @covers WMDE\Fundraising\PaymentContext\UseCases\CheckIban\CheckIbanUseCase
 */
class CheckIbanUseCaseTest extends TestCase {

	private $bankDataGenerator;
	private $ibanBlocklist;
	private $ibanValidator;

	protected function setUp()/* The :void return type declaration that should be here would cause a BC issue */ {
		$this->bankDataGenerator = $this->newSucceedingBankDataGenerator();
		$this->ibanBlocklist = new IbanBlocklist( [] );
		$this->ibanValidator = $this->newSucceedingIbanValidator();
	}

	private function newSucceedingBankDataGenerator(): BankDataGenerator {
		$generator = $this->createMock( BankDataGenerator::class );

		$generator->method( $this->anything() )->willReturn( new ValidBankData() );

		return $generator;
	}

	private function newCheckIbanUseCase(): CheckIbanUseCase {
		return new CheckIbanUseCase( $this->bankDataGenerator, $this->ibanValidator, $this->ibanBlocklist );
	}

	private function newSucceedingIbanValidator(): IbanValidator {
		$validator = $this->createMock( IbanValidator::class );
		$validator->method( 'validate' )->willReturn( new ValidationResult() );
		return $validator;
	}

	public function testWhenIbanIsOnBlocklist_failureResponseIsReturned(): void {
		$this->ibanBlocklist = new IbanBlocklist( [ ValidBankData::IBAN ] );

		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->checkIban( new Iban( ValidBankData::IBAN ) );

		$this->assertFalse( $response->isSuccessful(), 'IBAN on block list should fail' );
	}

	public function testWhenIbanIsInvalid_failureResponseIsReturned(): void {
		$this->ibanValidator = $this->createMock( IbanValidator::class );
		$this->ibanValidator->method( 'validate' )->willReturn(
			new ValidationResult( new ConstraintViolation(
				ValidBankData::IBAN,
				'Too many odd digits'
			) )
		);

		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->checkIban( new Iban( ValidBankData::IBAN ) );

		$this->assertFalse( $response->isSuccessful(), 'Invalid IBAN should fail' );
	}

	public function testGivenValidIban_BankDataIsReturned(): void {
		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->checkIban( new Iban( ValidBankData::IBAN ) );

		$this->assertTrue( $response->isSuccessful(), 'Valid IBAN should generate success response' );
		$this->assertEquals( new ValidBankData(), $response->getBankData() );
	}
}
