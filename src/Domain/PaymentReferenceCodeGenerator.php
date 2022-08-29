<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

interface PaymentReferenceCodeGenerator {

	public function newPaymentReference( string $prefix ): PaymentReferenceCode;
}
