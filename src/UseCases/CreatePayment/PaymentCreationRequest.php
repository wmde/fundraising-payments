<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;

class PaymentCreationRequest {

	private DomainSpecificPaymentValidator $domainSpecificPaymentValidator;

	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $interval,
		public readonly string $paymentType,
		public readonly string $iban = '',
		public readonly string $bic = '',
		public readonly string $transferCodePrefix = ''
	) {
	}

	public function __toString(): string {
		return json_encode( get_object_vars( $this ) ) ? json_encode( get_object_vars( $this ) ) : '';
	}

	public function getDomainSpecificPaymentValidator(): DomainSpecificPaymentValidator {
		return $this->domainSpecificPaymentValidator;
	}

	public function setDomainSpecificPaymentValidator( DomainSpecificPaymentValidator $domainSpecificPaymentValidator ): void {
		$this->domainSpecificPaymentValidator = $domainSpecificPaymentValidator;
	}

}
