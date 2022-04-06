<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

class PayPal implements PaymentProviderURLGenerator {

	private const PAYMENT_RECUR = '1';
	private const PAYMENT_REATTEMPT = '1';
	private const PAYMENT_CYCLE_INFINITE = '0';
	private const PAYMENT_CYCLE_MONTHLY = 'M';

	private PayPalConfig $config;
	private PayPalPayment $payment;

	public function __construct( PayPalConfig $config, PayPalPayment $payment ) {
		$this->config = $config;
		$this->payment = $payment;
	}

	public function generateUrl( RequestContext $requestContext ): string {
		$params = array_merge(
			$this->getIntervalDependentParameters( $this->payment->getAmount(), $this->payment->getInterval()->value ),
			$this->getIntervalAgnosticParameters(
				$requestContext->itemId,
				$requestContext->invoiceId,
				$requestContext->updateToken,
				$requestContext->accessToken ),
			$this->getPaymentDelayParameters()
		);

		return $this->config->getPayPalBaseUrl() . http_build_query( $params );
	}

	/**
	 * @param int $itemId
	 * @param string $invoiceId
	 * @param string $updateToken
	 * @param string $accessToken
	 * @return array<string,mixed>
	 */
	private function getIntervalAgnosticParameters( int $itemId, string $invoiceId, string $updateToken, string $accessToken ): array {
		return [
			'business' => $this->config->getPayPalAccountAddress(),
			'currency_code' => 'EUR',
			'lc' => $this->config->getLocale(),
			'item_name' => $this->config->getTranslatableDescription()->getText( $this->payment->getAmount(), $this->payment->getInterval() ),
			'item_number' => $itemId,
			'invoice' => $invoiceId,
			'notify_url' => $this->config->getNotifyUrl(),
			'cancel_return' => $this->config->getCancelUrl(),
			'return' => $this->config->getReturnUrl() . '?id=' . $itemId . '&accessToken=' . $accessToken,
			'custom' => json_encode(
				[
					'sid' => $itemId,
					'utoken' => $updateToken
				]
			)
		];
	}

	/**
	 * @return array<string,mixed>
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
