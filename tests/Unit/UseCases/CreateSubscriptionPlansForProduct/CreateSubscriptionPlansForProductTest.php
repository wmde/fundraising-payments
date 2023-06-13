<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreateSubscriptionPlansForProduct;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakePayPalAPI;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlansForProductUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\ErrorResult;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\SuccessResult;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlansForProductUseCase
 */
class CreateSubscriptionPlansForProductTest extends TestCase {

	public function testPassingEmptyProductNameReturnsErrorResult(): void {
		$useCase = new CreateSubscriptionPlansForProductUseCase( new FakePayPalAPI() );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
	}

	public function testCreatesProducts(): void {
		$api = new FakePayPalAPI();
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'FUNProduct', 'F1', PaymentInterval::HalfYearly ) );

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
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'FUNProduct', 'Id1', PaymentInterval::HalfYearly ) );

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
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
	}

	public function testReturnsErrorResultWhenCreatingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'createProduct' )->willThrowException( new PayPalAPIException() );
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
	}

	// test given non-existing interval - it creates the interval as a subscription plan, generating the name for the plan from the intervals and product name
	// suggestion: "Recurring $intervalName payment for $productName"
	// test given existing intervals - it creates no subscription plan
	// test error checking when listing or creation was nor successful

	//example symphony commands / how the commands could look like:
		// create-plan donation-1 "Recurring Donation" monthly
		// create-plan [productId] [productName] [intervalName]
	// command should assume that credentials (API URL, key and id) come from the environment (getenv /symfony getenv),
	// in the variables PAYPAL_API_URL, PAYPAL_API_CLIENT_ID, PAYPAL_API_CLIENT_SECRET

	// Q: Descriptions? If we want them (for products) we need to pass them in the requests and command
}
