<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

class PayPalAPIConfig
{

	public function __construct(
		public readonly string $productName,
		public readonly string $returnURL,
		public readonly string $cancelURL,
	)
	{
	}
}
