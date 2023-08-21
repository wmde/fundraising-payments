<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

class DomainSpecificPaymentCreationRequest extends PaymentCreationRequest {

	public readonly DomainSpecificPaymentValidator $domainSpecificPaymentValidator;
	public readonly DomainSpecificContext $domainSpecificContext;
	public readonly URLAuthenticator $urlAuthenticator;

	public function __construct(
		int $amountInEuroCents,
		int $interval,
		string $paymentType,
		DomainSpecificPaymentValidator $domainSpecificPaymentValidator,
		DomainSpecificContext $domainSpecificContext,
		URLAuthenticator $urlAuthenticator,
		string $iban = '',
		string $bic = '',
		string $transferCodePrefix = ''
	) {
		$this->domainSpecificPaymentValidator = $domainSpecificPaymentValidator;
		$this->domainSpecificContext = $domainSpecificContext;
		$this->urlAuthenticator = $urlAuthenticator;
		parent::__construct( $amountInEuroCents, $interval, $paymentType, $iban, $bic, $transferCodePrefix );
	}

	public static function newFromBaseRequest(
		PaymentCreationRequest $request,
											  DomainSpecificPaymentValidator $domainSpecificPaymentValidator,
											  DomainSpecificContext $domainSpecificContext,
											  URLAuthenticator $urlAuthenticator
	): self {
		return new self(
			$request->amountInEuroCents,
			$request->interval,
			$request->paymentType,
			$domainSpecificPaymentValidator,
			$domainSpecificContext,
			$urlAuthenticator,
			$request->iban,
			$request->bic,
			$request->transferCodePrefix
		);
	}

	public function jsonSerialize(): mixed {
		$objectVars = parent::jsonSerialize();
		if ( !is_array( $objectVars ) ) {
			throw new \LogicException( 'parent::jsonSerialize() did not return an array' );
		}
		$objectVars['domainSpecificPaymentValidator'] = get_class( $this->domainSpecificPaymentValidator );
		unset( $objectVars['urlAuthenticator'] );
		return (object)$objectVars;
	}
}
