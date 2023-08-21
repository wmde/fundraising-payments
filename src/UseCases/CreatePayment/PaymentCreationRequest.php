<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

class PaymentCreationRequest implements \JsonSerializable, \Stringable {

	public function __construct(
		public readonly int $amountInEuroCents,
		public readonly int $interval,
		public readonly string $paymentType,
		public readonly string $iban = '',
		public readonly string $bic = '',
		public readonly string $transferCodePrefix = ''
	) {
	}

	public function jsonSerialize(): mixed {
		return get_object_vars( $this );
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
