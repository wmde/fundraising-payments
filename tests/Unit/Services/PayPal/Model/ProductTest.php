<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product
 */
class ProductTest extends TestCase {

	public function testToJSONSerialization(): void {
		$product = new Product( 'SerializationName', 'SerializationID', 'SerializationDescription' );

		$actualJSONOutput = $product->toJSON();

		$this->assertSame(
			'{"name":"SerializationName","id":"SerializationID","description":"SerializationDescription","category":"NONPROFIT","type":"SERVICE"}',
			$actualJSONOutput
		);
	}

	public function testNameMustNotBeEmptyString(): void {
		$this->expectException( \UnexpectedValueException::class );
		$product = new Product( '', 'bla', '' );
	}

	public function testIdMustNotBeEmptyString(): void {
		$this->expectException( \UnexpectedValueException::class );
		$product = new Product( 'bla', '', '' );
	}
}
