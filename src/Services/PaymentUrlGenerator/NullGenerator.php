<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;

class NullGenerator implements PaymentProviderURLGenerator {

	public function generateURL( DomainSpecificContext $requestContext ): string {
		return "";
	}
}
