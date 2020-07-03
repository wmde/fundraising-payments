<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

/**
 * Generates a bank transfer code.
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
interface TransferCodeGenerator {

	public function generateTransferCode( string $prefix ): string;

}
