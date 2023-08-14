<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPaymentIdentifier;

interface PayPalPaymentIdentifierRepository {
	public function storePayPalIdentifier( PayPalPaymentIdentifier $identifier ): void;
}
