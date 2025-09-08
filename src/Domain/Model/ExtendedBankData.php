<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * DTO for additional information about bank data.
 *
 * For accounting purposes IBAN and BIC are sufficient (in the EU, even BIC is not strictly needed).
 *
 * But for people with German bank accounts we offer the pre-IBAN legacy format conversion
 * and look up the name of their bank. We use DTO to represent that data.
 *
 * Any field except $iban may be an empty string
 */
class ExtendedBankData {
	public function __construct(
		public readonly Iban $iban,
		public readonly string $bic,
		public readonly string $account,
		public readonly string $bankCode,
		public readonly string $bankName
	) {
	}
}
