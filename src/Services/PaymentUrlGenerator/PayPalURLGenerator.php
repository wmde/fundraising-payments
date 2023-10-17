<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;

/**
 * This URL "Generator" does not really generate, but returns a URL that came from a PayPal API call
 */
class PayPalURLGenerator implements PaymentCompletionURLGenerator {

	public function __construct( private readonly string $url ) {
	}

	public function generateURL( DomainSpecificContext $requestContext ): string {
		return $this->url;
	}

}
