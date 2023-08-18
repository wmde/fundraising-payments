<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

/**
 * Add authentication tokens to a URL
 */
interface URLAuthenticator {
	/**
	 * Add authentication tokens to an application URL, e.g. to the donation confirmation page, the PayPal IPN notification route etc.
	 */
	public function addAuthenticationTokensToApplicationUrl( string $url ): string;

	/**
	 * For payment providers that expect specific parameters passed to them, we can request those parameters.
	 *
	 * For example, MicroPayment expects `token` and `utoken` parameters and will internally convert them to
	 * `accessToken` and `updateToken` when redirecting to the confirmation page.
	 *
	 * @param class-string $urlGeneratorClass
	 * @param string[] $requestedParameters
	 * @return array<string,string>
	 */
	public function getAuthenticationTokensForPaymentProviderUrl( string $urlGeneratorClass, array $requestedParameters ): array;
}
