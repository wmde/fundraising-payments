<?php

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;

interface PaypalAPI {

	/**
	 * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_list
	 * @return Product[]
	 */
	public function listProducts(): array;
}
