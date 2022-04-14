<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\PaymentReferenceCodeGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\RandomPaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\IncrementalCharacterIndexGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator\RandomPaymentReferenceCodeGenerator
 */
class RandomPaymentReferenceCodeGeneratorTest extends TestCase {

	public function testGeneratorProducesValidCodes(): void {
		$generator = new RandomPaymentReferenceCodeGenerator( new IncrementalCharacterIndexGenerator() );

		$this->assertSame( 'AA-ACD-EFK-K', $generator->newPaymentReference( 'AA' )->getFormattedCode() );
		$this->assertSame( 'XY-LMN-PRT-L', $generator->newPaymentReference( 'XY' )->getFormattedCode() );
		$this->assertSame( '49-WXY-Z34-X', $generator->newPaymentReference( '49' )->getFormattedCode() );
	}
}
