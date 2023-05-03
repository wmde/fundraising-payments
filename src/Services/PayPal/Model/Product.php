<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

class Product {

	public readonly string $category;
	public readonly string $type;

	public function __construct(
		public readonly string $name,
		public readonly ?string $id,
		public readonly ?string $description,
	) {
		// https://developer.paypal.com/docs/api/catalog-products/v1/#products_create
		$this->category = 'NONPROFIT';
		$this->type = 'SERVICE';
	}
}
