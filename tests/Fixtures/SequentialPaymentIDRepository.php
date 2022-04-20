<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class SequentialPaymentIDRepository implements PaymentIDRepository {

	private int $currentId;

	public function __construct( int $initialId ) {
		$this->currentId = $initialId;
	}

	public function getNewID(): int {
		$newId = $this->currentId;
		$this->currentId++;
		return $newId;
	}
}
