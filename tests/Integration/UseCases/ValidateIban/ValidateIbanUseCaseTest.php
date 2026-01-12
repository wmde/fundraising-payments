<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\UseCases\ValidateIban;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Services\KontoCheck\KontoCheckBankDataGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataFailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataSuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

#[CoversClass( ValidateIbanUseCase::class )]
class ValidateIbanUseCaseTest extends TestCase {

	private IbanBlockList $ibanBlocklist;
	private IbanValidator $ibanValidator;

	protected function setUp(): void {
		$this->ibanBlocklist = new IbanBlockList( [] );
		$this->ibanValidator = $this->newSucceedingIbanValidator();
	}

	private function newCheckIbanUseCase(): ValidateIbanUseCase {
		return new ValidateIbanUseCase( $this->ibanBlocklist, new KontoCheckBankDataGenerator( $this->ibanValidator ) );
	}

	private function newSucceedingIbanValidator(): IbanValidator {
		return $this->createConfiguredStub(
			IbanValidator::class,
			[ 'validate' => new ValidationResult() ]
		);
	}

	public function testSucceedingIbanCheckReturnsGeneratedBankData(): void {
		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->ibanIsValid( DirectDebitBankData::IBAN );

		$this->assertInstanceOf( BankDataSuccessResponse::class, $response );
		$this->assertEquals(
			new ExtendedBankData(
				new Iban( DirectDebitBankData::IBAN ),
				DirectDebitBankData::BIC,
				DirectDebitBankData::ACCOUNT,
				DirectDebitBankData::BANK_CODE,
				DirectDebitBankData::BANK_NAME
			),
			$response->bankData
		);
	}

	public function testWhenIbanIsOnBlocklist_failureResponseIsReturned(): void {
		$this->ibanBlocklist = new IbanBlockList( [ DirectDebitBankData::IBAN ] );

		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->ibanIsValid( DirectDebitBankData::IBAN );

		$this->assertInstanceOf( BankDataFailureResponse::class, $response );
	}

	public function testWhenIbanIsInvalid_failureResponseIsReturned(): void {
		$this->ibanValidator = $this->createConfiguredStub(
			IbanValidator::class,
			[
				'validate' => new ValidationResult(
					new ConstraintViolation(
						DirectDebitBankData::IBAN,
						'Too many odd digits'
					)
				),
			]
		);

		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->ibanIsValid( DirectDebitBankData::IBAN );

		$this->assertInstanceOf( BankDataFailureResponse::class, $response );
	}
}
