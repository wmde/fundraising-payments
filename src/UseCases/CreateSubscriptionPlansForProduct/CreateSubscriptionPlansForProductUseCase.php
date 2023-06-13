<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

class CreateSubscriptionPlansForProductUseCase {

	public function __construct(
		private PaypalAPI $api
	) {
	}

	public function create( CreateSubscriptionPlanRequest $request ): SuccessResult|ErrorResult {
		$createdProducts = [];

		try {
			$alreadyExistingProducts = $this->getExistingProducts( $request->id );
		} catch ( PayPalAPIException $e ) {
			return new ErrorResult( $e->getMessage() );
		}

		if ( count( $alreadyExistingProducts ) == 0 ) {
			try {
				$createdProducts[] = $this->api->createProduct( new Product( $request->productName, $request->id ) );
			} catch ( \Exception $e ) {
				return new ErrorResult( $e->getMessage() );
			}
		}
		return new SuccessResult( $createdProducts, $alreadyExistingProducts );
	}

	/**
	 * @param string $id
	 * @return Product[]
	 */
	public function getExistingProducts( string $id ): array {
		$alreadyExistingProducts = [];
		foreach ( $this->api->listProducts() as $retrievedProduct ) {
			if ( $retrievedProduct->id === $id ) {
				$alreadyExistingProducts[] = $retrievedProduct;
				break;
			}
		}
		return $alreadyExistingProducts;
	}

}
