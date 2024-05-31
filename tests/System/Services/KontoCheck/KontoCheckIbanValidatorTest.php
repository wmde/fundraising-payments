<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\KontoCheck;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\KontoCheck\KontoCheckIbanValidator;

/**
 * Valid IBAN number examples taken from http://www.iban-rechner.eu/ibancalculator/iban.html#examples.
 */
#[CoversClass( KontoCheckIbanValidator::class )]
#[RequiresPhpExtension( 'konto_check' )]
class KontoCheckIbanValidatorTest extends TestCase {

	private function newValidator(): KontoCheckIbanValidator {
		return new KontoCheckIbanValidator();
	}

	/**
	 * @return array<int,array{string}>
	 */
	public static function validIbanProvider(): array {
		return [
			[ 'DE89370400440532013000' ],
			[ 'AT611904300234573201' ],
			[ 'CH9300762011623852957' ],
			[ 'BE68539007547034' ],
			[ 'IT60X0542811101000000123456' ],
			[ 'LI21088100002324013AA' ],
			[ 'LU280019400644750000' ],
		];
	}

	#[DataProvider( 'validIbanProvider' )]
	public function testGivenValidIban_validateReturnsTrue( string $iban ): void {
		$validator = $this->newValidator();
		$this->assertTrue( $validator->validate( $iban )->isSuccessful() );
	}

	/**
	 * @return array<int,array{string}>
	 */
	public static function wellFormedInvalidIbanProvider(): array {
		return [
			[ 'DE01234567890123456789' ],
			[ 'AT012345678901234567' ],
			[ 'CH0123456Ab0123456789' ],
			[ 'BE01234567890123' ],
			[ 'IT01A0123456789Ab0123456789' ],
			[ 'LI0123456Ab0123456789' ],
			[ 'LU01234Abc0123456789' ],
		];
	}

	#[DataProvider( 'wellFormedInvalidIbanProvider' )]
	public function testGivenWellFormedButInvalidIban_validateReturnsFalse( string $iban ): void {
		$validator = $this->newValidator();
		$this->assertFalse( $validator->validate( $iban )->isSuccessful() );
	}

	/**
	 * @return array<int,array{string}>
	 */
	public static function notWellFormedIbanProvider(): array {
		return [
			[ 'DE0123456789012345678' ],
			[ 'DE012345678901234567890' ],
			[ 'DEa0123456789012345678' ],
			[ 'DE0123456789a012345678' ]
		];
	}

	#[DataProvider( 'notWellFormedIbanProvider' )]
	public function testGivenNotWellFormedIban_validateReturnsFalse( string $iban ): void {
		$validator = $this->newValidator();
		$this->assertFalse( $validator->validate( $iban )->isSuccessful() );
	}

	#[DataProvider( 'notWellFormedIbanProvider' )]
	public function testGivenNotWellFormedIban_validationResultIsOneViolationWithStringifiedIban( string $malformedIban ): void {
		$validator = $this->newValidator();

		$violations = $validator->validate( $malformedIban )->getViolations();

		$this->assertCount( 1, $violations );
		$this->assertSame( $malformedIban, $violations[0]->getValue() );
	}
}
