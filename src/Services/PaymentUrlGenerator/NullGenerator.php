<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;

class NullGenerator implements PaymentProviderURLGenerator {

	public function generateURL( RequestContext $requestContext ): string {
		return "";
	}
}
