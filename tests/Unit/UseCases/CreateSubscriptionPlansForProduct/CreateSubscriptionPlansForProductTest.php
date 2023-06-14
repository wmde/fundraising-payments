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
		return new Product( 'P1', $id, '' );
	}

	private static function createSubscriptionPlan( string $productId, PaymentInterval $interval ): SubscriptionPlan {
		$product = self::createProduct( $productId );
		$planName = "Recurring " . $interval->name . " payment for " . $product->name;
		return new SubscriptionPlan( $planName, $product->id, $interval->value );
	}

	/** @dataProvider apiDataProvider */
	public function testFetchesOrCreatesNewProductsAndPlansAndGivesSuccessResult( array $products, array $subscriptionPlans, bool $productExists, bool $subscriptionPlanExists ): void {
		$product = self::createProduct( "id1" );
		$subscriptionPlan = self::createSubscriptionPlan( 'id1', PaymentInterval::HalfYearly );

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
		$this->assertTrue( $api->hasProduct( $product ) );
		$this->assertTrue( $api->hasSubscriptionPlan( $subscriptionPlan ) );
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
