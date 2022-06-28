<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

interface PaymentIdRepository {
	public function getNewId(): int;
}
