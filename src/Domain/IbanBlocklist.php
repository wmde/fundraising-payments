<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @license GPL-2.0-or-later
 */
class IbanBlocklist {

	/**
	 * @var string[]
	 */
	private $blockedIbans;

	/**
	 * @param string[] $blockedIbans
	 */
	public function __construct( array $blockedIbans ) {
		$this->blockedIbans = $blockedIbans;
	}

	public function isIbanBlocked( Iban $iban ): bool {
		return in_array( $iban->toString(), $this->blockedIbans );
	}

}
