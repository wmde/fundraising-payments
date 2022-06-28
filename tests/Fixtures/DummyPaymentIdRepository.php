<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class DummyPaymentIdRepository implements PaymentIdRepository {
	public function getNewId(): int {
		throw new \LogicException( 'ID generation should not be called in this code path' );
	}

}
