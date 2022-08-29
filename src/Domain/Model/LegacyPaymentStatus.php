<?php
// phpcs:ignoreFile -- Until phpcs has 8.1 enum support, see https://github.com/squizlabs/PHP_CodeSniffer/issues/3479
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * These values are a mixture of payment-type dependent status and
 * actions (booking, cancelling) that were performed on the payment.
 *
 * The status will be stored in the donation (which may add another status for moderation).
 *
 * When https://phabricator.wikimedia.org/T281853 is done, you should delete this class and all code that uses it.
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
