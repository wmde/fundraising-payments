<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class SequentialPaymentIdRepository implements PaymentIdRepository {

	private int $currentId;

	public function __construct( int $initialId ) {
		$this->currentId = $initialId;
	}

	public function getNewId(): int {
		$newId = $this->currentId;
		$this->currentId++;
		return $newId;
	}
}
