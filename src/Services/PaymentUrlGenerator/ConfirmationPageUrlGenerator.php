<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

/**
 * This generator does not create a redirect URL to a payment provider,
 * but to a confirmation page of the fundraising application.
 */
class ConfirmationPageUrlGenerator implements PaymentCompletionURLGenerator {
	public function __construct(
		private readonly string $confirmationPageUrl,
		private readonly URLAuthenticator $urlAuthenticator
	) {
	}

	public function generateURL( DomainSpecificContext $requestContext ): string {
		return $this->urlAuthenticator->addAuthenticationTokensToApplicationUrl( $this->confirmationPageUrl );
	}
}
