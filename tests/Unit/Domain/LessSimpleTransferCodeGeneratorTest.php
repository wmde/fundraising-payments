<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\LessSimpleTransferCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\LessSimpleTransferCodeValidator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\LessSimpleTransferCodeGenerator
 *
 * @license GPL-2.0-or-later
 */
class LessSimpleTransferCodeGeneratorTest extends TestCase {

	private const NUM_RANDOM_SAMPLES = 100;

	/**
	 * @dataProvider characterAndCodeProvider
	 */
	public function testGenerateBankTransferCode( string $expectedCode, string $usedCharacters, string $prefix ): void {
		$generator = LessSimpleTransferCodeGenerator::newDeterministicGenerator(
			$this->newFixedCharacterGenerator( $usedCharacters )
		);

		$this->assertSame( $expectedCode, $generator->generateTransferCode( $prefix ) );
	}

	/**
	 * @return iterable<array{string,string,string}>
	 */
	public function characterAndCodeProvider(): iterable {
		yield [ 'XW-ACD-EFK-4', 'ACDEFKLMNPRSTWXYZ349ACDEF', 'XW' ];
		yield [ 'XW-AAA-AAA-M', 'AAAAAAAAAAAAAAAAAAAAAAAAA', 'XW' ];
		yield [ 'XW-CAA-AAA-L', 'CAAAAAAAAAAAAAAAAAAAAAAAA', 'XW' ];
		yield [ 'XW-ACA-CAC-X', 'ACACACACACACACACACACACACA', 'XW' ];
		yield [ 'XR-ACD-EFK-4', 'ACDEFKLMNPRSTWXYZ349', 'XR' ];
	}

	/**
	 * @param string $characters
	 *
	 * @return \Generator<string>
	 */
	private function newFixedCharacterGenerator( string $characters ): \Generator {
		yield from str_split( $characters );
	}

	public function testRandomGeneratorProducesValidCodes(): void {
		$generator = LessSimpleTransferCodeGenerator::newRandomGenerator();
		$validator = new LessSimpleTransferCodeValidator();
		for ( $i = 0; $i < self::NUM_RANDOM_SAMPLES; $i++ ) {
			$code = $generator->generateTransferCode( 'XD' );
			$this->assertTrue( $validator->transferCodeIsValid( $code ) );
		}
	}
}
