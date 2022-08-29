<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

class IbanBlockList {

	/**
	 * @var string[]
	 */
	private array $blockedIbans;

	/**
	 * @param string[] $blockedIbans
	 */
	public function __construct( array $blockedIbans ) {
		$this->blockedIbans = $blockedIbans;
	}

	public function isIbanBlocked( string $iban ): bool {
		return in_array( $iban, $this->blockedIbans );
	}

}
