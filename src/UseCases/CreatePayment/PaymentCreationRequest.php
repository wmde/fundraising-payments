<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

class PaymentCreationRequest implements \JsonSerializable, \Stringable {

	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $interval,
		public readonly string $paymentType,
		public readonly DomainSpecificPaymentValidator $domainSpecificPaymentValidator,
		public readonly DomainSpecificContext $domainSpecificContext,
		public readonly URLAuthenticator $urlAuthenticator,
		public readonly string $iban = '',
		public readonly string $bic = '',
		public readonly string $transferCodePrefix = ''
	) {
	}

	public static function newFromParameters(
		PaymentParameters $parameters,
		DomainSpecificPaymentValidator $domainSpecificPaymentValidator,
		DomainSpecificContext $domainSpecificContext,
		URLAuthenticator $urlAuthenticator
	): self {
		return new self(
			$parameters->amountInEuroCents,
			$parameters->interval,
			$parameters->paymentType,
			$domainSpecificPaymentValidator,
			$domainSpecificContext,
			$urlAuthenticator,
			$parameters->iban,
			$parameters->bic,
			$parameters->transferCodePrefix
		);
	}

	public function getParameters(): PaymentParameters {
		return new PaymentParameters(
			$this->amountInEuroCents,
			$this->interval,
			$this->paymentType,
			$this->iban,
			$this->bic,
			$this->transferCodePrefix
		);
	}

	public function jsonSerialize(): mixed {
		$objectVars = get_object_vars( $this );
		$objectVars['domainSpecificPaymentValidator'] = get_class( $this->domainSpecificPaymentValidator );
		unset( $objectVars['urlAuthenticator'] );
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
