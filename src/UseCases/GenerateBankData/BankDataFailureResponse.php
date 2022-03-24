<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData;

class BankDataFailureResponse {
	public function __construct(
		public readonly string $message
	) {
	}
}
