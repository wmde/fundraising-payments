<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI as PayPalAPIClient;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\SofortClient;

class PaymentURLFactory implements UrlGeneratorFactory {

	/**
	 * @param CreditCardConfig $creditCardConfig
	 * @param PayPalConfig|PayPalAPIConfig $payPalConfig Until we have activated the new API-based PayPal payments, this might be a legacy "PayPalConfig" and will use the legacy URL generator internally
	 * @param PayPalAPIClient $paypalAPIClient
	 * @param SofortConfig $sofortConfig
	 * @param SofortClient $sofortClient
	 */
	public function __construct(
		private readonly CreditCardConfig $creditCardConfig,
		private readonly PayPalConfig|PayPalAPIConfig $payPalConfig,
		private readonly PayPalAPIClient $paypalAPIClient,
		private readonly SofortConfig $sofortConfig,
		private readonly SofortClient $sofortClient,
	) {
	}

	public function createURLGenerator( Payment $payment ): PaymentProviderURLGenerator {
		return match ( true ) {
			$payment instanceof SofortPayment => new Sofort( $this->sofortConfig, $this->sofortClient, $payment ),
			$payment instanceof CreditCardPayment => new CreditCard( $this->creditCardConfig, $payment ),
			$payment instanceof PayPalPayment => $this->payPalConfig instanceof PayPalAPIConfig ?
				new PayPalAPI( $this->paypalAPIClient, $this->payPalConfig, $payment) :
				new PayPal( $this->payPalConfig, $payment ),
			default => new NullGenerator(),
		};
	}
}
