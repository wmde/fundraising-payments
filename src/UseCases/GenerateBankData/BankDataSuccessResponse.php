<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;

class BankDataSuccessResponse {

	public function __construct(
		public readonly BankData $bankData
	) {
	}
}
