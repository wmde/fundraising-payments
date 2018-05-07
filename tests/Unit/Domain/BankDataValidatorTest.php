<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use WMDE\Fundraising\PaymentContext\Domain\BankDataValidator;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\BankDataValidator
 *
 * @license GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class BankDataValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider invalidBankDataProvider
	 */
	public function testFieldsMissing_validationFails( string $iban, string $bic, string $bankName,
		string $bankCode, string $account, string $message ): void {

		$bankDataValidator = $this->newBankDataValidator();
		$bankData = $this->newBankData( $iban, $bic, $bankName, $bankCode, $account );
		$this->assertFalse( $bankDataValidator->validate( $bankData )->isSuccessful(), $message );
	}

	public function invalidBankDataProvider(): array {
		return [
			[
				'',
				'SCROUSDBXXX',
				'',
				'',
				'',
				'BIC is not sufficient',
			],
			[
				'',
				'',
				'Scrooge Bank',
				'',
				'',
				'Bank name is not sufficient',
			],
			[
				'',
				'',
				'Scrooge Bank',
				'124567',
				'12345678',
				'Old-Style bank data is not sufficient',
			],
			[
				'DE00123456789012345678',
				'SCROUSDBXXX',
				str_repeat( 'Cats', 500 ),
				'124567',
				'12345678',
				'Bank name must not be too long',
			],
			[
				'DE00123456789012345678',
				'SCROUSDBXXX',
				'Scrooge Bank',
				'0000000000124567',
				'000000000012345678',
				'Old-Style bank data must not be too long',
			],
			[
				'DE00123456789012345678',
				' BCEELULL ',
				'',
				'',
				'',
				'BIC must not contain spaces',
			],
			[
				'DE00123456789012345678',
				'No BIC',
				'Scrooge Bank',
				'',
				'',
				'BIC must be well-formed',
			],
		];
	}

	public function testGivenFailingIbanValidator_validationFails() {
		$failingIbanValidator = $this->getMockBuilder( IbanValidator::class )->disableOriginalConstructor()->getMock();
		$failingIbanValidator->method( 'validate' )
			->willReturn( new ValidationResult( new ConstraintViolation( '', 'IBAN smells funny' ) ) );
		$bankData = $this->newBankData( 'DE00123456789012345678', 'SCROUSDBXXX', 'Scrooge Bank',
			'12345678', '1234567890' );
		$validator = new BankDataValidator( $failingIbanValidator );

		$this->assertFalse( $validator->validate( $bankData )->isSuccessful() );
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

	private function newBankData( string $iban, string $bic, string $bankName, string $bankCode, string $account ): BankData {
		return ( new BankData() )
			->setIban( new Iban( $iban ) )
			->setBic( $bic )
			->setBankName( $bankName )
			->setBankCode( $bankCode )
			->setAccount( $account )
			->freeze();
	}

	private function newBankDataValidator(): BankDataValidator {
		$ibanValidatorMock = $this->getMockBuilder( IbanValidator::class )->disableOriginalConstructor()->getMock();
		$ibanValidatorMock->method( 'validate' )
			->willReturn( new ValidationResult() );

		return new BankDataValidator( $ibanValidatorMock );
	}
}
