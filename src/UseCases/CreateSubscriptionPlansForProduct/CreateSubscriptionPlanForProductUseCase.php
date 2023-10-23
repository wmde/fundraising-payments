<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\PayPalAPIException;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

class CreateSubscriptionPlanForProductUseCase {

	public function __construct(
		private readonly PaypalAPI $api
	) {
	}

	public function create( CreateSubscriptionPlanRequest $request ): SuccessResult|ErrorResult {
		// Create product if needed
		try {
			$resultProduct = $this->productAlreadyExists( $request->productId );
		} catch ( PayPalAPIException $e ) {
			return new ErrorResult( $e->getMessage() );
		}

		if ( $resultProduct === null ) {
			$productAlreadyExisted = false;
			try {
				$resultProduct = $this->api->createProduct( new Product( $request->productId, $request->productName ) );
			} catch ( \Exception $e ) {
				return new ErrorResult( $e->getMessage() );
			}
		} else {
			$productAlreadyExisted = true;
		}

		// get plan, if it exists
		try {
			$resultSubscriptionPlan = $this->getPlanForProductAndInterval( $request->productId, $request->interval );
		} catch ( PayPalAPIException $e ) {
			return new ErrorResult( $e->getMessage() );
		}

		// Create plan if it doesn't exist
		if ( $resultSubscriptionPlan === null ) {
			$planAlreadyExistsForThisProduct = false;
			try {
				$resultSubscriptionPlan = $this->api->createSubscriptionPlanForProduct(
					new SubscriptionPlan(
						$request->planName,
						$request->productId,
						$request->interval
					)
				);
			} catch ( \Exception $e ) {
				return new ErrorResult( $e->getMessage() );
			}
		} else {
			$planAlreadyExistsForThisProduct = true;
		}

		return new SuccessResult( $resultProduct, $productAlreadyExisted, $resultSubscriptionPlan, $planAlreadyExistsForThisProduct );
	}

	private function productAlreadyExists( string $id ): ?Product {
		foreach ( $this->api->listProducts() as $retrievedProduct ) {
			if ( $retrievedProduct->id === $id ) {
				return $retrievedProduct;
			}
		}
		return null;
	}

	private function getPlanForProductAndInterval( string $productId, PaymentInterval $interval ): ?SubscriptionPlan {
		foreach ( $this->api->listSubscriptionPlansForProduct( $productId ) as $plan ) {
			if ( $plan->monthlyInterval === $interval ) {
				return $plan;
			}
		}
		return null;
	}

}
