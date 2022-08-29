<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * This interface will output the legacy data for storing payment data in the "data blob" of donations/memberships.
 * As long as code is reading that legacy data (exporter, detail view of FOC), we need to preserve this interface.
 * When the full refactoring is done, remove this interface and all its implementations.
 *
 * For bookable payment types (Sofort, Credit Card, PayPal), this should return an empty array when the payment is not booked.
 */
interface LegacyDataTransformer {
	public function getLegacyData(): LegacyPaymentData;
}
