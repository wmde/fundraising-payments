<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI as PayPalAPIClient;

class PaymentURLFactory implements UrlGeneratorFactory {

	/**
	 * @param CreditCardURLGeneratorConfig $creditCardConfig
	 * @param LegacyPayPalURLGeneratorConfig|PayPalAPIURLGeneratorConfig $payPalConfig Until we have activated the new API-based PayPal payments, this might be a legacy "PayPalConfig" and will use the legacy URL generator internally
	 * @param PayPalAPIClient $paypalAPIClient
	 * @param SofortURLGeneratorConfig $sofortConfig
	 * @param SofortClient $sofortClient
	 */
	public function __construct(
		private readonly CreditCardURLGeneratorConfig $creditCardConfig,
		private readonly LegacyPayPalURLGeneratorConfig|PayPalAPIURLGeneratorConfig $payPalConfig,
		private readonly PayPalAPIClient $paypalAPIClient,
		private readonly SofortURLGeneratorConfig $sofortConfig,
		private readonly SofortClient $sofortClient,
	) {
	}

	public function createURLGenerator( Payment $payment ): PaymentProviderURLGenerator {
		return match ( true ) {
			$payment instanceof SofortPayment => new SofortURLGenerator( $this->sofortConfig, $this->sofortClient, $payment ),
			$payment instanceof CreditCardPayment => new CreditCardURLGenerator( $this->creditCardConfig, $payment ),
			$payment instanceof PayPalPayment => $this->payPalConfig instanceof PayPalAPIURLGeneratorConfig ?
				new PayPalAPIURLGenerator( $this->paypalAPIClient, $this->payPalConfig, $payment ) :
				new LegacyPayPalURLGenerator( $this->payPalConfig, $payment ),
			default => new NullGenerator(),
		};
	}
}
