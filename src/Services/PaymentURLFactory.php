<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\FeeChangePayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\ConfirmationPageUrlGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\SofortClient;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGeneratorConfig;

/**
 * This class creates a payment-specific {@see PaymentCompletionURLGenerator}.
 *
 * For external payments, this will create the payment provider URL to redirect to.
 * For "local" payments, this will create an application-specific confirmation page (membership, donation, etc.),
 * which is why the $confirmationPageURL is a parameter.
 *
 * NOTE: Whenever you add a new payment type, you *have* to return a new URL generator for it!
 */
class PaymentURLFactory implements UrlGeneratorFactory {

	public function __construct(
		private readonly CreditCardURLGeneratorConfig $creditCardConfig,
		private readonly LegacyPayPalURLGeneratorConfig $legacyPayPalConfig,
		private readonly SofortURLGeneratorConfig $sofortConfig,
		private readonly SofortClient $sofortClient,
		private readonly string $confirmationPageUrl,
		private readonly bool $useLegacyPayPalUrlGenerator = true
	) {
	}

	public function createURLGenerator( Payment $payment, URLAuthenticator $authenticator ): PaymentCompletionURLGenerator {
		return match ( true ) {
			$payment instanceof SofortPayment => new SofortURLGenerator( $this->sofortConfig, $this->sofortClient, $authenticator, $payment ),
			$payment instanceof CreditCardPayment => new CreditCardURLGenerator( $this->creditCardConfig, $authenticator, $payment ),
			$payment instanceof PayPalPayment => $this->createPayPalUrlGenerator( $payment, $authenticator ),
			$payment instanceof DirectDebitPayment => new ConfirmationPageUrlGenerator( $this->confirmationPageUrl, $authenticator ),
			$payment instanceof BankTransferPayment => new ConfirmationPageUrlGenerator( $this->confirmationPageUrl, $authenticator ),
			$payment instanceof FeeChangePayment => new ConfirmationPageUrlGenerator( $this->confirmationPageUrl, $authenticator ),
			default => throw new \InvalidArgumentException( 'Unknown payment type: ' . get_class( $payment ) ),
		};
	}

	public function createPayPalUrlGenerator( PayPalPayment $payPalPayment, URLAuthenticator $authenticator ): PaymentCompletionURLGenerator {
		// TODO: Remove when the application has switched completely to the PayPal API,
		//       and we don't need the feature flag any more
		//       See https://phabricator.wikimedia.org/T329159
		if ( $this->useLegacyPayPalUrlGenerator ) {
			return new LegacyPayPalURLGenerator( $this->legacyPayPalConfig, $authenticator, $payPalPayment );
		}

		// The IncompletePayPalURLGenerator will be replaced inside the use case with a PayPalURLGenerator,
		// we need a default here to fulfill the type requirements
		// TODO: When one-time payments are supported, always return IncompletePayPalURLGenerator
		//       See https://phabricator.wikimedia.org/T344263
		if ( $payPalPayment->getInterval()->isRecurring() ) {
			return new IncompletePayPalURLGenerator( $payPalPayment );
		} else {
			return new LegacyPayPalURLGenerator( $this->legacyPayPalConfig, $authenticator, $payPalPayment );
		}
	}
}
