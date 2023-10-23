<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\UrlGenerator;

interface PaymentCompletionURLGenerator {

	/**
	 * Generate a URL to use (refer the donor to) to finalize the payment on a 3rd party payment provider page
	 * or redirect them to the confirmation page of the application (for "local" payment types, e.g. Direct Debit or Bank Transfer)
	 *
	 * @param DomainSpecificContext $requestContext
	 * @return string
	 */
	public function generateURL( DomainSpecificContext $requestContext ): string;
}
