<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DeterministicPaymentReferenceGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator
 */
class PaymentReferenceCodeGeneratorTest extends TestCase {
	/**
	 * @dataProvider characterAndCodeProvider
	 */
	public function testGeneratesCodeWithNextAvailableCharacter( string $expectedCode, string $usedCharacters, string $prefix ): void {
		$generator = new DeterministicPaymentReferenceGenerator( $usedCharacters );

		$reference = $generator->newPaymentReference( $prefix );

		$this->assertSame( $expectedCode, $reference->getFormattedCode() );
	}

	/**
	 * @return iterable<array{string,string,string}>
	 */
	public function characterAndCodeProvider(): iterable {
		yield [ 'XW-ACD-EFK-4', 'ACDEFKLMNPRTWXYZ349', 'XW' ];
		yield [ 'XW-AAA-AAA-M', 'AAAAAAAAAAAAAAAAAAAAAAAAA', 'XW' ];
		yield [ 'XW-CAA-AAA-L', 'CAAAAAAAAAAAAAAAAAAAAAAAA', 'XW' ];
		yield [ 'XW-ACA-CAC-X', 'ACACACACACACACACACACACACA', 'XW' ];
		yield [ 'XR-ACD-EFK-4', 'ACDEFKLMNPRTWXYZ349', 'XR' ];
	}

}
