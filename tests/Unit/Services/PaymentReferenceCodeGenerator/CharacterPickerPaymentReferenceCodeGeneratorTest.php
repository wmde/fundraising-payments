<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PaymentReferenceCodeGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\CharacterPickerPaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\RandomCharacterIndexGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\IncrementalCharacterIndexGenerator;

#[CoversClass( CharacterPickerPaymentReferenceCodeGenerator::class )]
class CharacterPickerPaymentReferenceCodeGeneratorTest extends TestCase {

	private const NUM_RANDOM_SAMPLES = 1000;

	public function testGeneratorProducesValidCodes(): void {
		$generator = new CharacterPickerPaymentReferenceCodeGenerator( new IncrementalCharacterIndexGenerator() );

		$this->assertSame( 'AA-ACD-EFK-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
		$this->assertSame( 'XY-LMN-PRT-L', $generator->newPaymentReference( 'XY' )->getFormattedCode() );
		$this->assertSame( '49-WXY-Z34-X', $generator->newPaymentReference( '49' )->getFormattedCode() );
	}

	/**
	 * This test creates random codes to assert that they are not invalid (which would throw an exception)
	 *
	 * @see https://github.com/sebastianbergmann/phpunit/issues/3016
	 *
	 * @return void
	 */
	public function testRandomGeneratorProducesValidCodes(): void {
		$generator = new CharacterPickerPaymentReferenceCodeGenerator( new RandomCharacterIndexGenerator() );
		$exception = null;

		try {
			for ( $i = 0; $i < self::NUM_RANDOM_SAMPLES; $i++ ) {
				$generator->newPaymentReference( 'XD' );
			}
		} catch ( \UnexpectedValueException $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception, 'Generated random codes must not throw exception' );
	}
}
