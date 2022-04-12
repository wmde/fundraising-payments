<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\BankDataValidationResult;
use WMDE\Fundraising\PaymentContext\Domain\BankDataValidator;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\BankDataValidator
 */
class BankDataValidatorTest extends TestCase {

	/**
	 * @dataProvider invalidBankDataProvider
	 */
	public function testFieldsMissing_validationFails( string $iban, string $bic, string $bankName,
		string $bankCode, string $account, string $expectedViolation, string $expectedSource, string $message ): void {
		$bankDataValidator = $this->newBankDataValidator();
		$bankData = $this->newBankData( $iban, $bic, $bankName, $bankCode, $account );

		$validationResult = $bankDataValidator->validate( $bankData );

		$this->assertFalse( $validationResult->isSuccessful(), $message );
		$this->assertEquals( $expectedViolation, $validationResult->getViolations()[0]->getMessageIdentifier() );
		$this->assertEquals( $expectedSource, $validationResult->getViolations()[0]->getSource() );
	}

	/**
	 * @return array<array{string,string,string,string,string,string,string}>
	 */
	public function invalidBankDataProvider(): array {
		return [
			[
				'',
				'SCROUSDBXXX',
				'',
				'',
				'',
				BankDataValidationResult::VIOLATION_MISSING,
				BankDataValidationResult::SOURCE_IBAN,
				'BIC is not sufficient',
			],
			[
				'',
				'',
				'Scrooge Bank',
				'',
				'',
				BankDataValidationResult::VIOLATION_MISSING,
				BankDataValidationResult::SOURCE_IBAN,
				'Bank name is not sufficient',
			],
			[
				'',
				'',
				'Scrooge Bank',
				'124567',
				'12345678',
				BankDataValidationResult::VIOLATION_MISSING,
				BankDataValidationResult::SOURCE_IBAN,
				'Old-Style bank data is not sufficient',
			],
			[
				'DE00123456789012345678',
				'SCROUSDBXXX',
				str_repeat( 'Cats', 500 ),
				'124567',
				'12345678',
				BankDataValidationResult::VIOLATION_WRONG_LENGTH,
				BankDataValidationResult::SOURCE_BANK_NAME,
				'Bank name must not be too long',
			],
			[
				'DE00123456789012345678',
				'SCROUSDBXXX',
				'Scrooge Bank',
				'0000000000124567',
				'000000000012345678',
				BankDataValidationResult::VIOLATION_WRONG_LENGTH,
				BankDataValidationResult::SOURCE_BANK_ACCOUNT,
				'Old-Style bank data must not be too long',
			],
			[
				'DE00123456789012345678',
				' BCEELULL ',
				'',
				'',
				'',
				BankDataValidationResult::VIOLATION_INVALID_BIC,
				BankDataValidationResult::SOURCE_BIC,
				'BIC must not contain spaces',
			],
			[
				'DE00123456789012345678',
				'No BIC',
				'Scrooge Bank',
				'',
				'',
				BankDataValidationResult::VIOLATION_INVALID_BIC,
				BankDataValidationResult::SOURCE_BIC,
				'BIC must be well-formed',
			],
		];
	}

	public function testGivenFailingIbanValidator_validationFails(): void {
		$failingIbanValidator = $this->getMockBuilder( IbanValidator::class )->disableOriginalConstructor()->getMock();
		$failingIbanValidator->method( 'validate' )
			->willReturn( new ValidationResult( new ConstraintViolation( '', 'IBAN smells funny' ) ) );
		$bankData = $this->newBankData( 'DE00123456789012345678', 'SCROUSDBXXX', 'Scrooge Bank',
			'12345678', '1234567890' );
		$validator = new BankDataValidator( $failingIbanValidator );

		$result = $validator->validate( $bankData );

		$this->assertFalse( $result->isSuccessful() );
		$this->assertEquals( BankDataValidationResult::SOURCE_IBAN, $result->getViolations()[0]->getSource() );
	}

	/**
	 * @dataProvider validBankDataProvider
	 */
	public function testAllRequiredFieldsGiven_validationSucceeds( string $iban, string $bic, string $bankName,
		string $bankCode, string $account, string $message ): void {
		$bankData = $this->newBankData( $iban, $bic, $bankName, $bankCode, $account );
		$bankDataValidator = $this->newBankDataValidator();

		$this->assertTrue( $bankDataValidator->validate( $bankData )->isSuccessful(), $message );
	}

	/**
	 * @return array<array{string,string,string,string,string,string}>
	 */
	public function validBankDataProvider(): array {
		return [
			[
				'DB00123456789012345678',
				'',
				'',
				'',
				'',
				'Single IBAN is valid',
			],
			[
				'DB00123456789012345678',
				'RZTIAT22263',
				'',
				'',
				'',
				'Long BIC is valid',
			],
			[
				'DB00123456789012345678',
				'BCEELULL',
				'',
				'',
				'',
				'Short BIC is valid',
			],
			[
				'DB00123456789012345678',
				'BELADEBEXXX',
				'',
				'',
				'',
				'BIC with XXX branch name is valid',
			],
			[
				'DB00123456789012345678',
				'SCROUSDBXXX',
				'Scrooge Bank',
				'',
				'',
				'IBAN, BIC and Bank name are valid',
			],
			[
				'DE00123456789012345678',
				'SCROUSDBXXX',
				'Scrooge Bank',
				'12345678',
				'1234567890',
				'Full set of payment data is valid',
			],
		];
	}

	private function newBankData( string $iban, string $bic, string $bankName, string $bankCode, string $account ): ExtendedBankData {
		return new ExtendedBankData( new Iban( $iban ), $bic, $account, $bankCode, $bankName );
	}

	private function newBankDataValidator(): BankDataValidator {
		$ibanValidatorMock = $this->getMockBuilder( IbanValidator::class )->disableOriginalConstructor()->getMock();
		$ibanValidatorMock->method( 'validate' )
			->willReturn( new ValidationResult() );

		return new BankDataValidator( $ibanValidatorMock );
	}
}
