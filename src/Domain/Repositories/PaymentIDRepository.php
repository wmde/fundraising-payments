<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Repositories;

interface PaymentIDRepository {
	public function getNewID(): int;
}
