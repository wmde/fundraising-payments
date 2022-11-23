<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

class FailureResponse {

	private const ALREADY_COMPLETED = 'Payment is already completed';

	public function __construct(
		public readonly string $message
	) {
	}

	public static function newAlreadyCompletedResponse(): self {
		return new self( self::ALREADY_COMPLETED );
	}

	public function paymentWasAlreadyCompleted(): bool {
		return $this->message === self::ALREADY_COMPLETED;
	}
}
