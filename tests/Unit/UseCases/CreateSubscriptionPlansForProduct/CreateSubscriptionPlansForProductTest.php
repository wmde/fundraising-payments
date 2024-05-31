<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreateSubscriptionPlansForProduct;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\PayPalAPIException;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakePayPalAPIForSetup;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanForProductUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\ErrorResult;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\SuccessResult;

#[CoversClass( CreateSubscriptionPlanForProductUseCase::class )]
#[CoversClass( CreateSubscriptionPlanRequest::class )]
#[CoversClass( ErrorResult::class )]
#[CoversClass( SuccessResult::class )]
class CreateSubscriptionPlansForProductTest extends TestCase {

	private const SUBSCRIPTION_PLAN_ID = 'P-0HVWVNKK2LCV2VN57N79TLENELT78EKL';

	public function testPassingEmptyProductNameReturnsErrorResult(): void {
		$useCase = new CreateSubscriptionPlanForProductUseCase( new FakePayPalAPIForSetup() );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( '', '', PaymentInterval::HalfYearly, 'blabla' ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Name and Id must not be empty', $result->message );
	}

	public function testPassingEmptySubscriptionPlanReturnsErrorResult(): void {
		$useCase = new CreateSubscriptionPlanForProductUseCase( new FakePayPalAPIForSetup() );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'bla', 'blabla', PaymentInterval::HalfYearly, '' ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Subscription plan name must not be empty', $result->message );
	}

	public function testReturnsErrorResultWhenListingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'listProducts' )->willThrowException( new PayPalAPIException( 'Listing products not allowed' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'ProductId-1', 'ProductName-1', PaymentInterval::HalfYearly, 'Half-Yearly Payment' ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Listing products not allowed', $result->message );
	}

	public function testReturnsErrorResultWhenCreatingProductIsNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'createProduct' )->willThrowException( new PayPalAPIException( 'Failed to create product' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest( 'ProductId-2', 'ProductName-2', PaymentInterval::HalfYearly, 'Half-Yearly Payment' ) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Failed to create product', $result->message );
	}

	public function testThrowsExceptionWhenRequestedWithOneTimePaymentInterval(): void {
		$this->expectException( UnexpectedValueException::class );
		new CreateSubscriptionPlanRequest( '', '', PaymentInterval::OneTime, 'One-Time Payment' );
	}

	/**
	 * @return iterable<string,array{Product[],SubscriptionPlan[],bool,bool}>
	 */
	public static function apiDataProvider(): iterable {
		yield 'no pre-existing product and plan' => [ [], [], false, false ];
		yield 'product already exists, create new plan' => [ [ self::createProduct( "id1" ) ], [], true, false ];
		yield 'different product exists, create new product and plan' => [ [ self::createProduct( 'id42' ) ], [], false, false ];
		yield 'different product and with a plan exists, create new product and plan' => [
			[ self::createProduct( 'id666' ) ],
			[ new SubscriptionPlan( 'Half-Yearly payment for product', 'id666', PaymentInterval::HalfYearly, self::SUBSCRIPTION_PLAN_ID ) ],
			false,
			false
		];
		yield 'product already existed, with a different plan, create new plan for it' => [
			[ self::createProduct( 'id1' ) ],
			[ new SubscriptionPlan( 'Monthly payment for product', 'id1', PaymentInterval::Monthly, self::SUBSCRIPTION_PLAN_ID ) ],
			true,
			false
		];
	}

	private static function createProduct( string $id ): Product {
		return new Product( $id, 'P1', '' );
	}

	/**
	 * @param Product[] $products
	 * @param SubscriptionPlan[] $subscriptionPlans
	 */
	#[DataProvider( 'apiDataProvider' )]
	public function testFetchesOrCreatesNewProductsAndPlansAndGivesSuccessResult( array $products, array $subscriptionPlans, bool $productExists, bool $subscriptionPlanExists ): void {
		$product = self::createProduct( "id1" );
		$expectedSubscriptionPlan = new SubscriptionPlan( 'A test plan', 'id1', PaymentInterval::HalfYearly, FakePayPalAPIForSetup::GENERATED_ID );

		$api = new FakePayPalAPIForSetup( $products, $subscriptionPlans );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest(
				$product->id,
				$product->name,
				PaymentInterval::HalfYearly,
				'A test plan'
			)
		);

		$expectedResult = new SuccessResult(
			$product,
			$productExists,
			$expectedSubscriptionPlan,
			$subscriptionPlanExists
		);
		$this->assertEquals( $expectedResult, $result );
		$this->assertTrue( $api->hasProduct( $product ) );
		$this->assertTrue( $api->hasSubscriptionPlan( $expectedSubscriptionPlan ) );
	}

	public function testThrowsErrorWhenCreatingPlanWasNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'createSubscriptionPlanForProduct' )->willThrowException( new PayPalAPIException( 'Creation of subscription plan failed' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest(
			'ProductId-3',
			'ProductName-3',
			PaymentInterval::HalfYearly,
			'Half-Yearly Payment'
		) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Creation of subscription plan failed', $result->message );
	}

	public function testThrowsErrorWhenListingSubscriptionPlanWasNotSuccessful(): void {
		$api = $this->createStub( PaypalAPI::class );
		$api->method( 'listSubscriptionPlansForProduct' )->willThrowException( new PayPalAPIException( 'Listing of subscription plan failed' ) );
		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );

		$result = $useCase->create( new CreateSubscriptionPlanRequest(
			'ProductId-4',
			'ProductName-4',
			PaymentInterval::HalfYearly,
			'Half-Yearly Payment'
		) );

		$this->assertInstanceOf( ErrorResult::class, $result );
		$this->assertSame( 'Listing of subscription plan failed', $result->message );
	}
}
