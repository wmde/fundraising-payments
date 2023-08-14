<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\UrlGenerator;

interface PaymentProviderURLGenerator {

	/**
	 * Generate a URL to use (refer the donor to) to finalize a purchase on a 3rd party payment provider page  or internal confirmation page
	 *
	 * @param RequestContext $requestContext
	 * @return string
	 */
	public function generateURL( RequestContext $requestContext ): string;
}
