<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;

class PaymentURLFactory {

	public function __construct(
		private CreditCardConfig $creditCardConfig,
		private PayPalConfig $payPalConfig,
		private SofortConfig $sofortConfig,
		private SofortClient $sofortClient ) {
	}

	public function createURLGenerator( string $paymentType, AdditionalPaymentData $additionalPaymentData ): PaymentProviderURLGenerator {
		return match ( $paymentType ) {
			'SUB' => new Sofort( $this->sofortConfig, $this->sofortClient, $additionalPaymentData ),
			'MCP' => new CreditCard( $this->creditCardConfig, $additionalPaymentData ),
			'PPL' => new PayPal( $this->payPalConfig, $additionalPaymentData ),
			default => new NullGenerator(),
		};
	}
}
