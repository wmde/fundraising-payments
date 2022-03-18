<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;

/**
 * TODO: Refactor this when the kontocheck library is integrated properly
 *       It should be split into separate success and failure objects
 */
class IbanResponse {

	private function __construct(
		private ResponseStatus $status,
		private readonly ?BankData $bankData = null
	) {
	}

	public static function newSuccessResponse( BankData $bankData ): self {
		return new self( ResponseStatus::Success, $bankData );
	}

	public static function newFailureResponse(): self {
		return new self( ResponseStatus::Failure );
	}

	public function isSuccessful(): bool {
		return $this->status === ResponseStatus::Success;
	}

	public function getBankData(): BankData {
		if ( !$this->isSuccessful() ) {
			throw new \RuntimeException( 'Cannot get the bank data of a failure response' );
		}

		if ( $this->bankData === null ) {
			throw new \RuntimeException( 'Successful bank data does not exist' );
		}

		return $this->bankData;
	}

}
