<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\PayPalAPIException;

#[CoversClass( Order::class )]
class OrderTest extends TestCase {

	public function testCreateFromApiResponse(): void {
		$subscription = Order::from( [
			'id' => '1',
			'links' => [
				[
					"href" => "https://api-m.paypal.com/v2/checkout/orders/5O190127TN364715T",
					"rel" => "self",
					"method" => "GET"
				],
				[
					"href" => "https://www.paypal.com/checkoutnow?token=5O190127TN364715T",
					"rel" => "payer-action",
					"method" => "GET"
				]
			]
		] );

		$this->assertEquals(
			new Order(
				'1',
				'https://www.paypal.com/checkoutnow?token=5O190127TN364715T'
			),
			$subscription
		);
	}

	/**
	 * @param array<string,mixed> $responseBody
	 */
	#[DataProvider( 'responsesMissingProperID' )]
	public function testIdIsRequiredField( array $responseBody, string $exceptionMessage ): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( $exceptionMessage );
		$order = Order::from( $responseBody );
	}

	/**
	 * @return iterable<array{array<string,mixed>,string}>
	 */
	public static function responsesMissingProperID(): iterable {
		yield [ [ "id" => null ], 'Field "id" is required!' ];
		yield [ [ "id" => false ], "Id is not a valid string!" ];
		yield [ [ "id" => "" ], "Id is not a valid string!" ];
		yield [ [ "blabla" => "bla" ], 'Field "id" is required!' ];
		yield [ [], 'Field "id" is required!' ];
	}

	public function testUnsetLinksThrowsException(): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( "Fields must contain array with links!" );
		order::from( [ 'id' => 'id-5' ] );
	}

	public function testMissingUserActionLinksThrowsAnException(): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( "Link array did not contain approve link!" );
		Order::from( [
			'id' => 'id-5',
			'links' => [
				[
					"href" => "https://api-m.paypal.com/v2/checkout/orders/5O190127TN364715T",
					"rel" => "self",
					"method" => "GET"
				]
			]
		] );
	}
}
