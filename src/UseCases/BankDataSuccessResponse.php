<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases;

use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;

class BankDataSuccessResponse {

	public function __construct(
		public readonly ExtendedBankData $bankData
	) {
	}
}
