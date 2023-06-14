<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

class CreateSubscriptionPlansForProductUseCase {

	public function __construct(
		private PaypalAPI $api
	) {
	}

	public function create( CreateSubscriptionPlanRequest $request ): SuccessResult|ErrorResult {
		try {
			$resultProduct = $this->productAlreadyExists( $request->id );
		} catch ( PayPalAPIException $e ) {
			return new ErrorResult( $e->getMessage() );
		}

		if ( $resultProduct === null ) {
			$productAlreadyExisted = false;
			try {
				$resultProduct = $this->api->createProduct( new Product( $request->productName, $request->id ) );
			} catch ( \Exception $e ) {
				return new ErrorResult( $e->getMessage() );
			}
		} else {
			$productAlreadyExisted = true;
		}

		$planName = "Recurring " . $request->interval->name . " payment for " . $request->productName;
		$subscriptionPlan = new SubscriptionPlan(
			$planName,
			$request->id,
			$request->interval->value
		);

		try {
			$resultSubscriptionPlan = $this->planAlreadyExistsForThisProduct( $request->id, $subscriptionPlan );
		} catch ( PayPalAPIException $e ) {
			return new ErrorResult( $e->getMessage() );
		}

		if ( $resultSubscriptionPlan === null ) {
			$planAlreadyExistsForThisProduct = false;
			try {
				$resultSubscriptionPlan = $this->api->createSubscriptionPlanForProduct( $subscriptionPlan );
			} catch ( \Exception $e ) {
				return new ErrorResult( $e->getMessage() );
			}
		} else {
			$planAlreadyExistsForThisProduct = true;
		}

		return new SuccessResult( $resultProduct, $productAlreadyExisted, $resultSubscriptionPlan, $planAlreadyExistsForThisProduct );
	}

	public function productAlreadyExists( string $id ): ?Product {
		foreach ( $this->api->listProducts() as $retrievedProduct ) {
			if ( $retrievedProduct->id === $id ) {
				return $retrievedProduct;
			}
		}
		return null;
	}

	public function planAlreadyExistsForThisProduct( string $productId, SubscriptionPlan $subscriptionPlan ): ?SubscriptionPlan {
		foreach ( $this->api->listSubscriptionPlansForProduct( $productId ) as $plan ) {
			if ( $plan->monthlyInterval === $subscriptionPlan->monthlyInterval ) {
				return $subscriptionPlan;
			}
		}
		return null;
	}

}
