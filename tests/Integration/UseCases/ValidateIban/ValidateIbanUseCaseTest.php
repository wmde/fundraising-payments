<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\UseCases\ValidateIban;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @covers WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase
 */
class ValidateIbanUseCaseTest extends TestCase {

	private IbanBlockList $ibanBlocklist;
	private IbanValidator $ibanValidator;

	protected function setUp(): void {
		$this->ibanBlocklist = new IbanBlockList( [] );
		$this->ibanValidator = $this->newSucceedingIbanValidator();
	}

	private function newCheckIbanUseCase(): ValidateIbanUseCase {
		return new ValidateIbanUseCase( $this->ibanValidator, $this->ibanBlocklist );
	}

	private function newSucceedingIbanValidator(): IbanValidator {
		$validator = $this->createMock( IbanValidator::class );
		$validator->method( 'validate' )->willReturn( new ValidationResult() );
		return $validator;
	}

	public function testWhenIbanIsOnBlocklist_failureResponseIsReturned(): void {
		$this->ibanBlocklist = new IbanBlockList( [ DirectDebitBankData::IBAN ] );

		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->ibanIsValid( DirectDebitBankData::IBAN );

		$this->assertFalse( $response, 'IBAN on block list should fail' );
	}

	public function testWhenIbanIsInvalid_failureResponseIsReturned(): void {
		$this->ibanValidator = $this->createMock( IbanValidator::class );
		$this->ibanValidator->method( 'validate' )->willReturn(
			new ValidationResult( new ConstraintViolation(
				DirectDebitBankData::IBAN,
				'Too many odd digits'
			) )
		);

		$useCase = $this->newCheckIbanUseCase();
		$response = $useCase->ibanIsValid( DirectDebitBankData::IBAN );

		$this->assertFalse( $response, 'Invalid IBAN should fail' );
	}
}
