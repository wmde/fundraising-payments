<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * This interface will output the legacy data for storing payment data in the "data blob" of donations/memberships.
 * As long as code is reading that legacy data (exporter, detail view of FOC), we need to preserve this interface.
 * When the full refactoring is done, remove this interface and all its implementations
 */
interface LegacyDataTransformer {

	/**
	 * @return array<string, mixed>
	 */
	public function getLegacyData(): array;
}
