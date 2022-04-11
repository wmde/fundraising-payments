<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;

class PaymentURLFactory implements UrlGeneratorFactory {

	public function __construct(
		private CreditCardConfig $creditCardConfig,
		private PayPalConfig $payPalConfig,
		private SofortConfig $sofortConfig,
		private SofortClient $sofortClient ) {
	}

	public function createURLGenerator( Payment $payment ): PaymentProviderURLGenerator {
		$paymentType = get_class( $payment );
		return match ( $paymentType ) {
			SofortPayment::class => new Sofort( $this->sofortConfig, $this->sofortClient, $payment ),
			CreditCardPayment::class => new CreditCard( $this->creditCardConfig, $payment ),
			PayPalPayment::class => new PayPal( $this->payPalConfig, $payment ),
			default => new NullGenerator(),
		};
	}
}
