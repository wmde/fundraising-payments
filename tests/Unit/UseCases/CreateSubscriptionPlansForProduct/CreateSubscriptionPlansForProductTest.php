<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreateSubscriptionPlansForProduct;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakePayPalAPI;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlansForProduct;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\ErrorResult;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\SuccessResult;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlansForProduct
 */
class CreateSubscriptionPlansForProductTest extends TestCase {

	public function testPassingEmptyProductNameReturnsErrorResult(): void {
		$useCase = new CreateSubscriptionPlansForProduct( new FakePayPalAPI() );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', [] ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
	}

	public function testCreatesProducts(): void {
		$api = new FakePayPalAPI();
		$useCase = new CreateSubscriptionPlansForProduct( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'FUNProduct', 'F1', [] ) );

		$expectedProduct = new Product( 'FUNProduct', 'F1', '' );
		$expectedResult = new SuccessResult(
			[ $expectedProduct ],
			[]
		);
		$this->assertEquals( $expectedResult, $result );
		$this->assertEquals( [ $expectedProduct ], $api->getProducts() );
	}

	public function testSkipsProductsCreationIfTheyDoExist(): void {
		$product = new Product( 'P1', 'Id1', '' );
		$api = new FakePayPalAPI( [ $product ] );
		$useCase = new CreateSubscriptionPlansForProduct( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'FUNProduct', 'Id1', [] ) );

		$expectedResult = new SuccessResult(
			[],
			[ $product ]
		);
		$this->assertEquals( $expectedResult, $result );
		$this->assertEquals( [ $product ], $api->getProducts() );
	}

	public function testReturnsErrorResultWhenListingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'listProducts' )->willThrowException( new PayPalAPIException() );
		$useCase = new CreateSubscriptionPlansForProduct( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', [] ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
	}

	public function testReturnsErrorResultWhenCreatingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'createProduct' )->willThrowException( new PayPalAPIException() );
		$useCase = new CreateSubscriptionPlansForProduct( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', [] ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
	}
}
