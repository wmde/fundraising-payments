<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PayPalPaymentIdentifierRepository;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfig;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DefaultPaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapterFactory;

class PaymentProviderAdapterFactoryImplementation implements PaymentProviderAdapterFactory {
	/**
	 * @param PaypalAPI $paypalAPI
	 * @param PayPalPaymentProviderAdapterConfig $payPalAdapterConfig
	 * @param PayPalPaymentIdentifierRepository $paymentIdentifierRepository
	 * @param bool $useLegacyPaypalPaymentAdapter Feature flag. Remove when https://phabricator.wikimedia.org/T329159 (Use PayPal API) is completely done
	 */
	public function __construct(
		private readonly PaypalAPI $paypalAPI,
		private readonly PayPalPaymentProviderAdapterConfig $payPalAdapterConfig,
		private readonly PayPalPaymentIdentifierRepository $paymentIdentifierRepository,
		private readonly bool $useLegacyPaypalPaymentAdapter = false
	) {
	}

	public function createProvider( Payment $payment, URLAuthenticator $authenticator ): PaymentProviderAdapter {
		if ( $payment instanceof PayPalPayment && !$this->useLegacyPaypalPaymentAdapter ) {
			return new PayPalPaymentProviderAdapter(
				$this->paypalAPI,
				$this->payPalAdapterConfig,
				$this->paymentIdentifierRepository,
				$authenticator
			);
		}
		return new DefaultPaymentProviderAdapter();
	}
}
