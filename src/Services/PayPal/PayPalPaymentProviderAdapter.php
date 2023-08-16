<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalSubscription;
use WMDE\Fundraising\PaymentContext\Domain\PayPalPaymentIdentifierRepository;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;

class PayPalPaymentProviderAdapter implements PaymentProviderAdapter {

	/**
	 * @var Subscription|null Used as cache to avoid multiple API calls
	 */
	private ?Subscription $subscription = null;

	public function __construct(
		private readonly PaypalAPI $paypalAPI,
		private readonly PayPalPaymentProviderAdapterConfig $config,
		private readonly PayPalPaymentIdentifierRepository $paymentIdentifierRepository
	) {
	}

	public function fetchAndStoreAdditionalData( Payment $payment, DomainSpecificContext $domainSpecificContext ): Payment {
		$this->checkIfPaymentIsPayPalPayment( $payment );

		if ( $payment->getInterval()->isRecurring() ) {
			$subscription = $this->createSubscriptionWithAPI( $payment );
			$identifier = new PayPalSubscription( $payment, $subscription->id );
			$this->paymentIdentifierRepository->storePayPalIdentifier( $identifier );
		}
		// We don't store the order id for one-time payments, because we don't need it.
		// It'll be passed in the `token` parameter of the URL when the user returns from PayPal, together with the donation ID
		return $payment;
	}

	public function modifyPaymentUrlGenerator( PaymentProviderURLGenerator $paymentProviderURLGenerator, DomainSpecificContext $domainSpecificContext ): PaymentProviderURLGenerator {
		if ( !( $paymentProviderURLGenerator instanceof IncompletePayPalURLGenerator ) ) {
			throw new \LogicException( sprintf(
				'Expected instance of %s, got %s',
				IncompletePayPalURLGenerator::class,
				get_class( $paymentProviderURLGenerator )
			) );
		}
		$payment = $paymentProviderURLGenerator->payment;
		if ( $payment->getInterval()->isRecurring() ) {
			$subscription = $this->createSubscriptionWithAPI( $payment );
			return new PayPalURLGenerator( $subscription->confirmationLink );
		} else {
			// TODO When implementing one-time-payments, pass a context value object to this method (similar to UrlGenerator\RequestContext)
			//      and take invoice id and order id from there.
			//      See https://phabricator.wikimedia.org/T344271 (refactoring URL generation logic)
			//          and https://phabricator.wikimedia.org/T344263 (implementing one-time payments)
			$params = new OrderParameters( (string)$payment->getId(), (string)$payment->getId(), $this->config->productName, $payment->getAmount(), $this->config->returnURL, $this->config->cancelURL );
			$order = $this->paypalAPI->createOrder( $params );
			return new PayPalURLGenerator( $order->confirmationLink );
		}
	}

	/**
	 * Create subscription with API, but use subscription property as cache, to avoid multiple API calls
	 */
	private function createSubscriptionWithAPI( PayPalPayment $payment ): Subscription {
		if ( $this->subscription === null ) {
			$subscriptionPlan = $this->config->subscriptionPlanMap[ $payment->getInterval()->name ];
			// TODO When implementing membership payments that need this, we'll need to get the start time from somewhere
			//      and replace the 'null' start time with value from the context.
			//      Recommendation: Replace UrlGenerator\RequestContext with a `PaymentContextParams` that contains the start time.
			//      Pass the context to fetchAndStoreAdditionalData and modifyPaymentUrlGenerator
			//      See https://phabricator.wikimedia.org/T344271 (refactoring URL generation logic), as a requirement for this
			$params = new SubscriptionParameters( $subscriptionPlan, $payment->getAmount(), $this->config->returnURL, $this->config->cancelURL, null );
			$this->subscription = $this->paypalAPI->createSubscription( $params );
		}
		return $this->subscription;
	}

	/**
	 * @phpstan-assert PayPalPayment $payment
	 */
	private function checkIfPaymentIsPayPalPayment( Payment $payment ): void {
		if ( !( $payment instanceof PayPalPayment ) ) {
			throw new \LogicException( sprintf(
				'%s only accepts %s, got %s',
				self::class,
				PayPalPayment::class,
				get_class( $payment )
			) );
		}
	}

}
