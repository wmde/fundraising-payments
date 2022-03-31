<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

class VerificationResponse {
	private bool $isValid;
	private string $message;

	private function __construct( bool $isValid, string $message ) {
		$this->isValid = $isValid;
		$this->message = $message;
	}

	public function isValid(): bool {
		return $this->isValid;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public static function newSuccessResponse( string $message = "" ): self {
		return new self( true, $message );
	}

	public static function newFailureResponse( string $message ): self {
		return new self( false, $message );
	}
}
