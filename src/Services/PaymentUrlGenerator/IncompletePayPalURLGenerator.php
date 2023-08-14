<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;

class IncompletePayPalURLGenerator implements PaymentProviderURLGenerator {

	public function __construct( public readonly PayPalPayment $payment ) {
	}

	public function generateURL( RequestContext $requestContext ): string {
		throw new \LogicException( sprintf(
			'This instance should be replaced with an instance of %s, using %s',
			PayPalURLGenerator::class,
			// TODO replace interface name with PayPal class name implementation
			PaymentProviderAdapter::class
		) );
	}

}
