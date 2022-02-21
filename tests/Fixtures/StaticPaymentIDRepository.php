<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class StaticPaymentIDRepository implements PaymentIDRepository {

	public const STATIC_ID = 42;

	public function getNewID(): int {
		return self::STATIC_ID;
	}
}
