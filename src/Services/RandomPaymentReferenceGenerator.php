<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;

class RandomPaymentReferenceGenerator extends PaymentReferenceCodeGenerator {
	private int $maxRandom;

	public function __construct() {
		parent::__construct();
		$this->maxRandom = count( $this->characters ) - 1;
	}

	protected function getNextCharacterIndex(): int {
		return mt_rand( 0, $this->maxRandom );
	}

}
