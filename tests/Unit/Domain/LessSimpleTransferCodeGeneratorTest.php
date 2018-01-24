<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\LessSimpleTransferCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\LessSimpleTransferCodeValidator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\LessSimpleTransferCodeGenerator
 *
 * @licence GNU GPL v2+
 */
class LessSimpleTransferCodeGeneratorTest extends TestCase {

	/**
	 * @dataProvider characterAndCodeProvider
	 */
	public function testGenerateBankTransferCode( string $expectedCode, string $usedCharacters, string $prefix ): void {
		$generator = LessSimpleTransferCodeGenerator::newDeterministicGenerator(
			$this->newFixedCharacterGenerator( $usedCharacters )
		);

		$this->assertSame( $expectedCode, $generator->generateTransferCode( $prefix ) );
	}

	public function characterAndCodeProvider(): iterable {
		yield [ 'XW-ACD-EFK-4', 'ACDEFKLMNPRSTWXYZ349ACDEF', 'XW' ];
		yield [ 'XW-AAA-AAA-M', 'AAAAAAAAAAAAAAAAAAAAAAAAA', 'XW' ];
		yield [ 'XW-CAA-AAA-L', 'CAAAAAAAAAAAAAAAAAAAAAAAA', 'XW' ];
		yield [ 'XW-ACA-CAC-X', 'ACACACACACACACACACACACACA', 'XW' ];
		yield [ 'XR-ACD-EFK-4', 'ACDEFKLMNPRSTWXYZ349', 'XR' ];
	}

	private function newFixedCharacterGenerator( string $characters ): \Generator {
		yield from str_split( $characters );
	}

	public function testRandomGeneratorProducesValidCodes(): void {
		$generator = LessSimpleTransferCodeGenerator::newRandomGenerator();
		$validator = new LessSimpleTransferCodeValidator();
		for ( $i = 0; $i < 42; $i++ ) {
			$code = $generator->generateTransferCode( 'XD' );
			$this->assertTrue( $validator->transferCodeIsValid( $code ) );
		}
	}

	/**
	 * @dataProvider tooShortPrefixProvider
	 */
	public function testGenerationWithShortPrefixCausesException( string $prefix ): void {
		$generator = LessSimpleTransferCodeGenerator::newRandomGenerator();

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The prefix must have a set length of 2 characters.' );

		$generator->generateTransferCode( $prefix );
	}

	public function tooShortPrefixProvider(): iterable {
		yield [ '' ];
		yield [ 'X' ];
	}

	public function testGenerationWithInvalidPrefixCharactersCausesException(): void {
		$generator = LessSimpleTransferCodeGenerator::newRandomGenerator();

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The prefix must only contain characters from the ALLOWED_CHARACTERS set.' );

		$generator->generateTransferCode( '5S' );
	}
}
