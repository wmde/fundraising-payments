<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode
 */
class PaymentReferenceCodeTest extends TestCase {
	/**
	 * @param string $prefix
	 * @param string $code
	 * @param string $checksum
	 * @param string $expectedInErrorMessage
	 * @return void
	 *
	 * @dataProvider invalidInputProvider
	 */
	public function testInvalidInputThrowsException( string $prefix, string $code, string $checksum, string $expectedInErrorMessage ): void {
		$this->expectException( \UnexpectedValueException::class );
		$this->expectDeprecationMessageMatches( $expectedInErrorMessage );

		new PaymentReferenceCode( $prefix, $code, $checksum );
	}

	/**
	 * @return array{string,string,string,string}[]
	 */
	public function invalidInputProvider(): array {
		return [
			'Prefix must be valid char' => [ '#', 'XYZDCA', 'T', '/prefix/' ],
			'Prefix must be 2 characters' => [ 'L', 'XYZDCA', 'T', '/prefix/', ],
			'Code must contain only allowed characters' => [ 'LE', '#YZDCA', 'T', '/code/' ],
			'Code must be 6 characters' => [ 'LE', 'XYZDCAAAA', 'T', '/code/' ],
			'Checksum must contain only allowed characters' => [ 'LE', 'XYZDCA', 'O', '/checksum/' ],
			'Checksum must be 1 character' => [ 'LE', 'XYZDCA', 'TT', '/checksum/' ],
			];
	}

	public function testCanBeConvertedToString(): void {
		$code = new PaymentReferenceCode( 'XW', 'DARE99', 'T' );

		$this->assertSame( 'XW-DAR-E99-T', (string)$code );
		$this->assertSame( 'XW-DAR-E99-T', $code->getFormattedCode() );
	}
}
