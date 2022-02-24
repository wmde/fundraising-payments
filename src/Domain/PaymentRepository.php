<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

interface PaymentRepository {
	public function storePayment( Payment $payment ): void;

	public function getPaymentById( int $id ): Payment;
}
