<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

interface PaymentAnonymizer {
	/**
	 * Anonymize individual payments
	 *
	 * @throws AnonymizationException
	 */
	public function anonymizeWithIds( int ...$paymentIds ): void;
}
