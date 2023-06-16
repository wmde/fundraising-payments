<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreateSubscriptionPlansForProduct;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakePayPalAPI;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanForProductUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\ErrorResult;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\SuccessResult;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanForProductUseCase
 */
class CreateSubscriptionPlansForProductTest extends TestCase {

	public function testPassingEmptyProductNameReturnsErrorResult(): void {
		$useCase = new CreateSubscriptionPlanForProductUseCase( new FakePayPalAPI() );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Name and Id must not be empty', $result->message );
	}

	public function testReturnsErrorResultWhenListingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'listProducts' )->willThrowException( new PayPalAPIException( 'Listing products not allowed' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'ProductId-1', 'ProductName-1', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Listing products not allowed', $result->message );
	}

	public function testReturnsErrorResultWhenCreatingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'createProduct' )->willThrowException( new PayPalAPIException( 'Failed to create product' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'ProductId-2', 'ProductName-2', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Failed to create product', $result->message );
	}

	/**
	 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest
	 */
	public function testThrowsExceptionWhenRequestedWithOneTimePaymentInterval(): void {
		$this->expectException( \UnexpectedValueException::class );
		new CreateSubscriptionPlanRequest( '', '', PaymentInterval::OneTime );
	}

	/**
	 * @return iterable<string,array{Product[],SubscriptionPlan[],bool,bool}>
	 */
	public static function apiDataProvider(): iterable {
		yield 'no pre-existing product and plan' => [ [] , [], false, false ];
		yield 'product already exists, create new plan' => [ [ self::createProduct( "id1" ) ], [], true, false ];
		yield 'different product exists, create new product and plan' => [ [ self::createProduct( 'id42' ) ], [], false, false ];
		yield 'different product and with a plan exists, create new product and plan' => [
			[ self::createProduct( 'id666' ) ],
			[ self::createSubscriptionPlan( 'id666', PaymentInterval::HalfYearly ) ],
			false,
			false
		];
		yield 'product already existed, with a different plan, create new plan for it' => [
			[ self::createProduct( 'id1' ) ],
			[ self::createSubscriptionPlan( 'id1', PaymentInterval::Monthly ) ],
			true,
			false
		];
	}

	private static function createProduct( string $id ): Product {
		return new Product( $id, 'P1', '' );
	}

	private static function createSubscriptionPlan( string $productId, PaymentInterval $interval ): SubscriptionPlan {
		$product = self::createProduct( $productId );
		$planName = "Recurring " . $interval->name . " payment for " . $product->name;
		return new SubscriptionPlan( $planName, $product->id, $interval );
	}

	/**
	 * @dataProvider apiDataProvider
	 * @param Product[] $products
	 * @param SubscriptionPlan[] $subscriptionPlans
	 */
	public function testFetchesOrCreatesNewProductsAndPlansAndGivesSuccessResult( array $products, array $subscriptionPlans, bool $productExists, bool $subscriptionPlanExists ): void {
		$product = self::createProduct( "id1" );
		$subscriptionPlan = self::createSubscriptionPlan( 'id1', PaymentInterval::HalfYearly );

		$api = new FakePayPalAPI( $products, $subscriptionPlans );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest(
				$product->id,
				$product->name,
				$subscriptionPlan->monthlyInterval
			)
		);

		$expectedResult = new SuccessResult(
			$product,
			$productExists,
			$subscriptionPlan,
			$subscriptionPlanExists
		);
		$this->assertEquals( $expectedResult, $result );
		$this->assertTrue( $api->hasProduct( $product ) );
		$this->assertTrue( $api->hasSubscriptionPlan( $subscriptionPlan ) );
	}

	public function testThrowsErrorWhenCreatingPlanWasNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'createSubscriptionPlanForProduct' )->willThrowException( new PayPalAPIException( 'Creation of subscription plan failed' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'ProductId-3', 'ProductName-3', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Creation of subscription plan failed', $result->message );
	}

	public function testThrowsErrorWhenListingSubscriptionPlanWasNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'listSubscriptionPlansForProduct' )->willThrowException( new PayPalAPIException( 'Listing of subscription plan failed' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'ProductId-4', 'ProductName-4', PaymentInterval::HalfYearly ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Listing of subscription plan failed', $result->message );
	}

	// example symphony commands / how the commands could look like:
		// create-plan donation-1 "Recurring Donation" monthly
		// create-plan [productId] [productName] [intervalName]
	// command should assume that credentials (API URL, key and id) come from the environment (getenv /symfony getenv),
	// in the variables PAYPAL_API_URL, PAYPAL_API_CLIENT_ID, PAYPAL_API_CLIENT_SECRET

	// Q: Descriptions? If we want them (for products) we need to pass them in the requests and command
}
