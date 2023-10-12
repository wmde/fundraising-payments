<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalOrder;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalSubscription;
use WMDE\Fundraising\PaymentContext\Domain\PayPalPaymentIdentifierRepository;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;

class PayPalPaymentProviderAdapter implements PaymentProviderAdapter {

	/**
	 * @var Subscription|null Used as cache to avoid multiple API calls
	 */
	private ?Subscription $subscription = null;

	/**
	 * @var Order|null Used as cache to avoid multiple API calls
	 */
	private ?Order $order = null;

	public function __construct(
		private readonly PaypalAPI $paypalAPI,
		private readonly PayPalPaymentProviderAdapterConfig $config,
		private readonly PayPalPaymentIdentifierRepository $paymentIdentifierRepository,
		private readonly URLAuthenticator $urlAuthenticator
	) {
	}

	public function fetchAndStoreAdditionalData( Payment $payment, DomainSpecificContext $domainSpecificContext ): Payment {
		$this->checkIfPaymentIsPayPalPayment( $payment );

		if ( $payment->getInterval()->isRecurring() ) {
			$subscription = $this->createSubscriptionWithAPI( $payment, $domainSpecificContext );
			$identifier = new PayPalSubscription( $payment, $subscription->id );
		} else {
			$order = $this->createOrderWithAPI( $payment, $domainSpecificContext );
			$identifier = new PayPalOrder( $payment, $order->id );
		}

		$this->paymentIdentifierRepository->storePayPalIdentifier( $identifier );
		return $payment;
	}

	public function modifyPaymentUrlGenerator( PaymentProviderURLGenerator $paymentProviderURLGenerator, DomainSpecificContext $domainSpecificContext ): PaymentProviderURLGenerator {
		if ( $paymentProviderURLGenerator instanceof LegacyPayPalURLGenerator ) {
			// All logic for domain-specific information and authentication encapsulated in LegacyPayPalURLGenerator
			return $paymentProviderURLGenerator;
		}
		if ( !( $paymentProviderURLGenerator instanceof IncompletePayPalURLGenerator ) ) {
			throw new \LogicException( sprintf(
				'Expected instance of %s, got %s',
				IncompletePayPalURLGenerator::class,
				get_class( $paymentProviderURLGenerator )
			) );
		}

		$payment = $paymentProviderURLGenerator->payment;
		if ( $payment->getInterval()->isRecurring() ) {
			$subscription = $this->createSubscriptionWithAPI( $payment, $domainSpecificContext );
			return new PayPalURLGenerator( $subscription->confirmationLink );
		} else {
			$order = $this->createOrderWithAPI( $payment, $domainSpecificContext );
			return new PayPalURLGenerator( $order->confirmationLink );
		}
	}

	/**
	 * Create subscription with API, but use subscription property as cache, to avoid multiple API calls
	 */
	private function createSubscriptionWithAPI( PayPalPayment $payment, DomainSpecificContext $domainSpecificContext ): Subscription {
		if ( $this->subscription === null ) {
			$subscriptionPlan = $this->config->subscriptionPlanMap[ $payment->getInterval()->name ];
			$params = new SubscriptionParameters(
				$subscriptionPlan,
				$payment->getAmount(),
				$this->urlAuthenticator->addAuthenticationTokensToApplicationUrl( $this->config->returnURL ),
				$this->urlAuthenticator->addAuthenticationTokensToApplicationUrl( $this->config->cancelURL ),
				$domainSpecificContext->startTimeForRecurringPayment
			);
			$this->subscription = $this->paypalAPI->createSubscription( $params );
		}
		return $this->subscription;
	}

	private function createOrderWithAPI( PayPalPayment $payment, DomainSpecificContext $domainSpecificContext, ): Model\Order {
		if ( $this->order === null ) {
			$params = new OrderParameters(
				(string)$domainSpecificContext->itemId,
				$domainSpecificContext->invoiceId,
				$this->config->productName,
				$payment->getAmount(),
				$this->urlAuthenticator->addAuthenticationTokensToApplicationUrl( $this->config->returnURL ),
				$this->urlAuthenticator->addAuthenticationTokensToApplicationUrl( $this->config->cancelURL )
			);
			$this->order = $this->paypalAPI->createOrder( $params );
		}
		return $this->order;
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
