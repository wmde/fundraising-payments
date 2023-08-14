<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;

/**
 * This URL "Generator" does not really generate, but returns a URL that came from a PayPal API call
 */
class PayPalURLGenerator implements PaymentProviderURLGenerator {

	public function __construct( private readonly string $url ) {
	}

	public function generateURL( RequestContext $requestContext ): string {
		return $this->url;
	}

}
