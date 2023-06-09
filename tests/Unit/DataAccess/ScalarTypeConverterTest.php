<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\ScalarTypeConverter;

/**
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\ScalarTypeConverter
 */
class ScalarTypeConverterTest extends TestCase {
	/**
	 * @dataProvider integerConvertibleScalars
	 */
	public function testItConvertToIntegers( mixed $value, int $expected ): void {
		$this->assertSame( $expected, ScalarTypeConverter::toInt( $value ) );
	}

	/**
	 * @dataProvider noScalars
	 */
	public function testToIntThrowsOnNonScalars( mixed $value ): void {
		$this->expectException( \InvalidArgumentException::class );
		ScalarTypeConverter::toInt( $value );
	}

	/**
	 * @return iterable<array{scalar,int}>
	 */
	public static function integerConvertibleScalars(): iterable {
		yield [ 0, 0 ];
		yield [ 1, 1 ];
		yield [ -1, -1 ];
		yield [ 1.2, 1 ];
		yield [ 1.8, 1 ];
		yield [ '', 0 ];
		yield [ '5', 5 ];
		yield [ '-15', -15 ];
		yield [ '5 ', 5 ];
		yield [ ' 5', 5 ];
		yield [ '055', 55 ];
		yield [ '0xd5', 0 ];
		yield [ 'NaN', 0 ];
		yield [ '1abc', 1 ];
		yield [ '2dogs', 2 ];
		yield [ '', 0 ];
		yield [ false, 0 ];
		yield [ true, 1 ];
	}

	/**
	 * @dataProvider stringConvertibleScalars
	 */
	public function testItConvertToString( mixed $value, string $expected ): void {
		$this->assertSame( $expected, ScalarTypeConverter::toString( $value ) );
	}

	/**
	 * @dataProvider noScalars
	 */
	public function testToStringThrowsOnNonScalars( mixed $value ): void {
		$this->expectException( \InvalidArgumentException::class );
		ScalarTypeConverter::toString( $value );
	}

	/**
	 * @return iterable<array{scalar,string}>
	 */
	public static function stringConvertibleScalars(): iterable {
		yield [ 0, '0' ];
		yield [ 1, '1' ];
		yield [ -1, '-1' ];
		yield [ 1.2, '1.2' ];
		yield [ 1.8, '1.8' ];
		yield [ 'aaaa', 'aaaa' ];
		yield [ '0xd5', '0xd5' ];
		yield [ '5', '5' ];
		yield [ '-15', '-15' ];
		yield [ false, '' ];
		yield [ true, '1' ];
	}

	/**
	 * @return iterable<array{mixed}>
	 */
	public static function noScalars(): iterable {
		yield [ null ];
		yield [ [] ];
		yield [ new \StdClass() ];
		// We don't test yielding a resource since unit tests should not interact with the system
	}

}
