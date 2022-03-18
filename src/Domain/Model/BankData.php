<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

class BankData {
	public function __construct(
		public readonly Iban $iban,
		public readonly string $bic,
		public readonly string $account,
		public readonly string $bankCode,
		public readonly string $bankName
	) {
	}
}
