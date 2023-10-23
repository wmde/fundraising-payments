<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;

class IncompletePayPalURLGenerator implements PaymentCompletionURLGenerator {

	public function __construct( public readonly PayPalPayment $payment ) {
	}

	public function generateURL( DomainSpecificContext $requestContext ): string {
		throw new \LogicException( sprintf(
			'This instance should be replaced with an instance of %s, using %s',
			PayPalURLGenerator::class,
			PaymentProviderAdapter::class
		) );
	}

}
