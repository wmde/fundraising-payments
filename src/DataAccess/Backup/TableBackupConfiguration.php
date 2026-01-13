<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Backup;

class TableBackupConfiguration {

	/**
	 * @param string $tableName One or more database table names, separated with spaces
	 * @param string $conditions SQL conditions to select the data that should be backed up from the table(s). Can be empty for "all data".
	 */
	public function __construct(
		public readonly string $tableName,
		public readonly string $conditions
	) {
	}
}
