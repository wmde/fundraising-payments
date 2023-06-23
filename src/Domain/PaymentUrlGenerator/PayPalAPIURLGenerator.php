<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI as APIClient;

class PayPalAPIURLGenerator implements PaymentProviderURLGenerator {

	public function __construct(
		public readonly APIClient $payPalApi,
		public readonly PayPalAPIURLGeneratorConfig $config,
		public readonly PayPalPayment $payment
	) {
	}

	public function generateURL( RequestContext $requestContext ): string {
		if ( $this->payment->getInterval()->isRecurring() ) {
			return $this->generateURLForRecurringPayment( $requestContext );

		} else {
			return $this->generateURLForOneTimePayment( $requestContext );
		}
	}

	public function generateURLForRecurringPayment( RequestContext $requestContext ): string {
		$subscriptionPlan = $this->config->subscriptionPlanMap[ $this->payment->getInterval()->name ];
		$subscriptionParameters = new SubscriptionParameters(
			$subscriptionPlan,
			$this->payment->getAmount(),
			$this->replacePlaceholdersInUrl( $this->config->returnURL, $requestContext ),
			$this->replacePlaceholdersInUrl( $this->config->cancelURL, $requestContext ),
		);
		return $this->payPalApi->createSubscription( $subscriptionParameters )->confirmationLink;
	}

	public function generateURLForOneTimePayment( RequestContext $requestContext ): string {
			$orderParameters = new OrderParameters(
				$requestContext->invoiceId,
				strval( $requestContext->itemId ),
				$this->config->productName,
				$this->payment->getAmount(),
				$this->replacePlaceholdersInUrl( $this->config->returnURL, $requestContext ),
				$this->replacePlaceholdersInUrl( $this->config->cancelURL, $requestContext ),
			);
		return $this->payPalApi->createOrder( $orderParameters )->confirmationLink;
	}

	private function replacePlaceholdersInUrl( string $urlTemplate, RequestContext $requestContext ): string {
		return str_replace(
			[ '{{id}}', '{{updateToken}}', '{{accessToken}}' ],
			[ $requestContext->itemId, $requestContext->updateToken, $requestContext->accessToken ],
			$urlTemplate
		);
	}

}
