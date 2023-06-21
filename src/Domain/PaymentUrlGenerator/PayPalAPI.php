<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use http\Url;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI as APIClient;


class PayPalAPI implements PaymentProviderURLGenerator {

	public function __construct(
		public readonly APIClient $payPalApi,
		public readonly PayPalAPIConfig $config,
		public readonly PayPalPayment $payment
	) {}

	public function generateURL( RequestContext $requestContext ): string {
		$orderParameters = new OrderParameters(
			$requestContext->invoiceId,
			strval( $requestContext->itemId ),
			$this->config->productName,
			$this->payment->getAmount(),
			str_replace(
				['{{id}}', '{{updateToken}}', '{{accessToken}}'],
				[$requestContext->itemId, $requestContext->updateToken, $requestContext->accessToken],
				$this->config->returnURL
			),
			""
		);
		return $this->payPalApi->createOrder( $orderParameters )->confirmationLink;
	}


}
