<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

/**
 * Generates a bank transfer code
 *
 * A transfer code is a reference code for the user to fill in when
 * they transfer money with their banking app.
 *
 * @todo return TransferCode class instead of string, extract prefix, and checksum
 */
interface TransferCodeGenerator {

	public function generateTransferCode( string $prefix ): string;

}
