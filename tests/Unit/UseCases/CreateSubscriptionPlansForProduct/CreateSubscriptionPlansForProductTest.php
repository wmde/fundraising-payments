<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreateSubscriptionPlansForProduct;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
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

	public function testCreatesProduct(): void {
		$api = new FakePayPalAPI();
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'FUNProduct', 'F1', PaymentInterval::HalfYearly ) );

		$expectedProduct = new Product( 'FUNProduct', 'F1', '' );
		$expectedResult = new SuccessResult(
			$expectedProduct,
			false
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
			$product,
			true
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

	public function testThrowsExceptionWhenRequestedWithOneTimePaymentInterval(): void {
		$this->expectException( \UnexpectedValueException::class );
		new CreateSubscriptionPlanRequest( '', '', PaymentInterval::OneTime );
	}

	public function testCreatesNewPlanIfRequestedIntervalDoesNotYetExist(): void {
		// todo create subscription plan
		// name for the plan: "Recurring $intervalName payment for $productName"
		$product = new Product( 'P1', 'Id1', '' );
		$planName = "Recurring " . PaymentInterval::HalfYearly->name . "payment for " . $product->name;
		$subscriptionPlan = new SubscriptionPlan( $planName, $product->id, PaymentInterval::HalfYearly->value );

		$api = new FakePayPalAPI( [], [] );
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest(
				$product->name,
				$product->id,
				PaymentInterval::from( $subscriptionPlan->monthlyInterval )
			)
		);

		$expectedResult = new SuccessResult( $product, false, $subscriptionPlan, false );
		$this->assertEquals( $expectedResult, $result );
		$this->assertEquals( [ $product ], $api->getProducts() );
	}

	public static function apiDataProvider(): iterable {
		yield [ [] , [], false, false ];
		yield [ [ self::createProduct() ], [], true, false ];
		yield [ [ self::createProduct() ], [], false, false ];
		yield [ [ self::createProduct() ], [ self::createSubscriptionPlan() ], true, true ];
		yield [ [ self::createProduct() ], [ self::createSubscriptionPlan() ], true, false ];
		yield [ [ self::createProduct() ], [ self::createSubscriptionPlan() ], false, false ];
	}

	private static function createProduct(): Product {
		return new Product( 'P1', 'Id1', '' );
	}

	private static function createSubscriptionPlan(): SubscriptionPlan {
		$product = self::createProduct();
		$planName = "Recurring " . PaymentInterval::HalfYearly->name . "payment for " . $product->name;
		return new SubscriptionPlan( $planName, $product->id, PaymentInterval::HalfYearly->value );
	}

	/** @dataProvider apiDataProvider */
	public function testDoesNotCreateNewPlanIfRequestedIntervalAlreadyExists( array $products, array $subscriptionPlans, bool $productExists, bool $subscriptionPlanExists ): void {
		// todo create subscription plan
		// name for the plan: "Recurring $intervalName payment for $productName"
		$product = self::createProduct();
		$subscriptionPlan = self::createSubscriptionPlan();

		$api = new FakePayPalAPI( $products, $subscriptionPlans );
		$useCase = new CreateSubscriptionPlansForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest(
				$product->name,
				$product->id,
				PaymentInterval::from( $subscriptionPlan->monthlyInterval )
			)
		);

		$expectedResult = new SuccessResult(
			$product,
			$productExists,
			$subscriptionPlan,
			$subscriptionPlanExists
		);
		$this->assertEquals( $expectedResult, $result );
		$this->assertEquals( [ $product ], $api->getProducts() );
	}

	public function testThrowsErrorWhenCreatingPlanWasNotSuccessful(): void {
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
