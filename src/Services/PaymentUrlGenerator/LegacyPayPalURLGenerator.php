<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

/**
 * URL generator that passes the PayPal parameters to the PayPal page via URL parameters
 * instead of calling the PayPal API.
 *
 * Until we support one-time payments with the PayPal API, we also use this legacy class for
 * one-time-payments, even when the PayPal API is used for recurring payments
 * (see https://phabricator.wikimedia.org/T344263 and {@see PaymentURLFactory})
 *
 * @deprecated Use PayPalAPI instead. See https://phabricator.wikimedia.org/T329159
 */
class LegacyPayPalURLGenerator implements PaymentProviderURLGenerator {

	private const PAYMENT_RECUR = '1';
	private const PAYMENT_REATTEMPT = '1';
	private const PAYMENT_CYCLE_INFINITE = '0';
	private const PAYMENT_CYCLE_MONTHLY = 'M';

	public function __construct(
		private readonly LegacyPayPalURLGeneratorConfig $config,
		private readonly URLAuthenticator $urlAuthenticator,
		private readonly PayPalPayment $payment
	) {
	}

	public function generateUrl( DomainSpecificContext $requestContext ): string {
		$params = array_merge(
			$this->getIntervalDependentParameters( $this->payment->getAmount(), $this->payment->getInterval()->value ),
			$this->getIntervalAgnosticParameters(
				$requestContext->itemId,
				$requestContext->invoiceId,
		 ),
			$this->getPaymentDelayParameters()
		);

		return $this->config->getPayPalBaseUrl() . http_build_query( $params );
	}

	/**
	 * @param int $itemId
	 * @param string $invoiceId
	 * @return array<string,mixed>
	 */
	private function getIntervalAgnosticParameters( int $itemId, string $invoiceId ): array {
		return [
			'business' => $this->config->getPayPalAccountAddress(),
			'currency_code' => 'EUR',
			'lc' => $this->config->getLocale(),
			'item_name' => $this->config->getTranslatableDescription()->getText( $this->payment->getAmount(), $this->payment->getInterval() ),
			'item_number' => $itemId,
			'invoice' => $invoiceId,
			'notify_url' => $this->config->getNotifyUrl(),
			'cancel_return' => $this->config->getCancelUrl(),
			'return' => $this->urlAuthenticator->addAuthenticationTokensToApplicationUrl(
				$this->config->getReturnUrl() . '?id=' . $itemId
			),
			...$this->urlAuthenticator->getAuthenticationTokensForPaymentProviderUrl(
				self::class,
				[ 'custom' ]
			)
		];
	}

	/**
	 * @return array<string,mixed>
	 * @deprecated The "Trial Period" was an attempt at using PayPal for memberships. We'll use the PayPal API with subscriptions instead.
	 */
	private function getPaymentDelayParameters(): array {
		if ( $this->config->getDelayInDays() > 0 ) {
			return $this->getDelayedSubscriptionParams( $this->config->getDelayInDays() );
		}
		return [];
	}

	/**
	 * @param Euro $amount
	 * @param int $interval
	 * @return array<string,mixed>
	 */
	private function getIntervalDependentParameters( Euro $amount, int $interval ): array {
		if ( $interval > 0 ) {
			return $this->getSubscriptionParams( $amount, $interval );
		}

		return $this->getSinglePaymentParams( $amount );
	}

	/**
	 * This method returns a set of parameters needed for recurring payments.
	 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/wp_standard_overview/
	 *
	 * @param Euro $amount
	 * @param int $interval
	 *
	 * @return array<string,mixed>
	 */
	private function getSubscriptionParams( Euro $amount, int $interval ): array {
		return [
			'cmd' => '_xclick-subscriptions',
			'no_shipping' => '1',
			'src' => self::PAYMENT_RECUR,
			'sra' => self::PAYMENT_REATTEMPT,
			'srt' => self::PAYMENT_CYCLE_INFINITE,
			'a3' => $amount->getEuroString(),
			'p3' => $interval,
			't3' => self::PAYMENT_CYCLE_MONTHLY,
		];
	}

	/**
	 * This method returns a set of parameters needed for delaying payments. It uses the parameters of one out of two
	 * trial periods supported by PayPal.
	 *
	 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/wp_standard_overview/
	 *
	 * @param int $delayInDays
	 *
	 * @return array<string,mixed>
	 * @deprecated The "Trial Period" was an attempt at using PayPal for memberships. We'll use the PayPal API with subscriptions instead.
	 */
	private function getDelayedSubscriptionParams( int $delayInDays ): array {
		return [
			'a1' => 0,
			'p1' => $delayInDays,
			't1' => 'D'
		];
	}

	/**
	 * This method returns a set of parameters needed for one time payments.
	 *
	 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/wp_standard_overview/
	 *
	 * @param Euro $amount
	 *
	 * @return array<string,string>
	 */
	private function getSinglePaymentParams( Euro $amount ): array {
		return [
			'cmd' => '_donations',
			'amount' => $amount->getEuroString()
		];
	}

}
