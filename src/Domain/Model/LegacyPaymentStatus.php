<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * These values are a mixture of payment-type dependent status and
 * actions (booking, cancelling) that were performed on the payment.
 *
 * The status will be stored in the donation (which may add another status for moderation).
 *
 * @todo Now that https://phabricator.wikimedia.org/T281853 is done, you can delete this class and all the classes that use it
 * @deprecated
 */
enum LegacyPaymentStatus: string {
	// direct debit
	case DIRECT_DEBIT = 'N';

	// bank transfer
	case BANK_TRANSFER = 'Z';

	// external payment, not notified by payment provider
	case EXTERNAL_INCOMPLETE = 'X';

	// external payment, notified by payment provider
	case EXTERNAL_BOOKED = 'B';
	case CANCELLED = 'D';
}
