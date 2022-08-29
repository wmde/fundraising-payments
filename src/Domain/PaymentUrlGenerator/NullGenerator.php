<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

class NullGenerator implements PaymentProviderURLGenerator {

	public function generateURL( RequestContext $requestContext ): string {
		return "";
	}
}
