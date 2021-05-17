<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;

class SofortTransactionData implements PaymentTransactionData {
	private DateTimeImmutable $valuationDate;

	public function __construct( DateTimeImmutable $valuationDate ) {
		$this->valuationDate = $valuationDate;
	}

	public function getValuationDate(): DateTimeImmutable {
		return $this->valuationDate;
	}

}
