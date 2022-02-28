<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentOverrideException;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

interface PaymentRepository {
	/**
	 * @param Payment $payment
	 * @return void
	 * @throws PaymentOverrideException
	 */
	public function storePayment( Payment $payment ): void;

	/**
	 * @param int $id
	 * @return Payment
	 * @throws PaymentNotFoundException
	 */
	public function getPaymentById( int $id ): Payment;
}
