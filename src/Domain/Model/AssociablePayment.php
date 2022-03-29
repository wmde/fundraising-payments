<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * @template T of Payment
 */
interface AssociablePayment {
	/**
	 * @param int $followUpPaymentId
	 * @return T
	 */
	public function createFollowUpPayment( int $followUpPaymentId ): Payment;
}
