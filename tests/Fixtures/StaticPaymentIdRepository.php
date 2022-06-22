<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class StaticPaymentIdRepository implements PaymentIdRepository {

	public const STATIC_ID = 42;

	public function getNewId(): int {
		return self::STATIC_ID;
	}
}
