<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;

class FixedPaymentReferenceCodeGenerator implements PaymentReferenceCodeGenerator {

	/**
	 * @var array<PaymentReferenceCode>
	 */
	private array $paymentReferenceCodes;
	private int $index = 0;

	/**
	 * @param array<PaymentReferenceCode> $paymentReferenceCodes
	 */
	public function __construct( array $paymentReferenceCodes ) {
		$this->paymentReferenceCodes = $paymentReferenceCodes;
	}

	public function newPaymentReference( string $prefix ): PaymentReferenceCode {
		return $this->paymentReferenceCodes[$this->index++];
	}
}
