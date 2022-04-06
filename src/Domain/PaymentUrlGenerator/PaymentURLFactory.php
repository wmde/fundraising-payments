<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;

class PaymentURLFactory {

	public function __construct(
		private CreditCardConfig $creditCardConfig,
		private PayPalConfig $payPalConfig,
		private SofortConfig $sofortConfig,
		private SofortClient $sofortClient ) {
	}

	public function createURLGenerator( Payment $payment ): PaymentProviderURLGenerator {
		return match ( true ) {
			$payment instanceof SofortPayment => new Sofort( $this->sofortConfig, $this->sofortClient, $payment ),
			$payment instanceof CreditCardPayment => new CreditCard( $this->creditCardConfig, $payment ),
			$payment instanceof PayPalPayment => new PayPal( $this->payPalConfig, $payment ),
			default => new NullGenerator(),
		};
	}
}
