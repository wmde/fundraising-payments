<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;

class PaymentCreationRequest implements \JsonSerializable, \Stringable {

	private DomainSpecificPaymentValidator $domainSpecificPaymentValidator;
	private ?DomainSpecificContext $domainSpecificContext = null;

	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $interval,
		public readonly string $paymentType,
		public readonly string $iban = '',
		public readonly string $bic = '',
		public readonly string $transferCodePrefix = ''
	) {
	}

	public function getDomainSpecificPaymentValidator(): DomainSpecificPaymentValidator {
		return $this->domainSpecificPaymentValidator;
	}

	public function setDomainSpecificPaymentValidator( DomainSpecificPaymentValidator $domainSpecificPaymentValidator ): void {
		$this->domainSpecificPaymentValidator = $domainSpecificPaymentValidator;
	}

	public function getDomainSpecificContext(): DomainSpecificContext {
		if ( $this->domainSpecificContext === null ) {
			throw new \LogicException( 'Domain specific context was not set before calling use case' );
		}
		return $this->domainSpecificContext;
	}

	public function setDomainSpecificContext( DomainSpecificContext $domainSpecificContext ): void {
		$this->domainSpecificContext = $domainSpecificContext;
	}

	public function jsonSerialize(): mixed {
		$objectVars = get_object_vars( $this );
		$objectVars['domainSpecificPaymentValidator'] = get_class( $this->domainSpecificPaymentValidator );
		return (object)$objectVars;
	}

	public function __toString(): string {
		$encodedResult = json_encode( $this->jsonSerialize() );
		if ( $encodedResult === false ) {
			return sprintf( "JSON encode error in %s: %s",
			__METHOD__,
			json_last_error_msg()
			);
		}
		return $encodedResult;
	}

}
