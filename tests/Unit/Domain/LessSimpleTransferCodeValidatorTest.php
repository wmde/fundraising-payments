<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\Frontend\PaymentContext\Domain\LessSimpleTransferCodeValidator;

/**
 * @covers \WMDE\Fundraising\Frontend\PaymentContext\Domain\LessSimpleTransferCodeValidator
 */
class LessSimpleTransferCodeValidatorTest extends TestCase {

	/**
	 * @dataProvider invalidCodeProvider
	 */
	public function testInvalidTransferCodesAreNotValid( string $invalidCode ): void {
		$validator = new LessSimpleTransferCodeValidator();
		$this->assertFalse( $validator->transferCodeIsValid( $invalidCode ) );
	}

	public function invalidCodeProvider(): iterable {
		yield 'Empty code' => [ '' ];
		yield 'Missing prefix' => [ '-XXX-XXX-X' ];
		yield 'Missing checksum' => [ 'X-XXX-XXX-' ];
		yield 'Very short' => [ 'XX--X' ];
		yield 'Bad character' => [ 'A2-5S7-DZU-1' ];
		yield 'Bad checksum' => [ 'XW-ACD-EFK-C' ];
	}

	/**
	 * @dataProvider characterAndCodeProvider
	 */
	public function testValidTransferCodesAreValid( string $transferCode ): void {
		$validator = new LessSimpleTransferCodeValidator();
		$this->assertTrue( $validator->transferCodeIsValid( $transferCode ) );
	}

	public function characterAndCodeProvider(): iterable {
		yield [ 'XW-A3d-EFT-Z '];
		yield [ 'XW_A3d_EFT_Z '];
		yield [ 'XW--A3d--EFT--Z '];
		yield [ 'XWA3dEFTZ '];

		yield [ 'XW-ACD-EFK-4' ];
		yield [ 'XW-AAA-AAA-M' ];
		yield [ 'XW-CAA-AAA-L' ];
		yield [ 'XW-ACA-CAC-X' ];

		yield [ 'XR-ACD-EFK-4' ];
		yield [ 'XR-aCD-EFK-4' ];
	}

}
