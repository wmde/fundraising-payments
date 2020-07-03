<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

/**
 * Adds days to a given base date.
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
interface PaymentDelayCalculator {

	public function calculateFirstPaymentDate(): \DateTime;

}
