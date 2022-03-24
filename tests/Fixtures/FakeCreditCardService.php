<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\CreditCardService;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class FakeCreditCardService implements CreditCardService {

	public function getExpirationDate( string $customerId ): CreditCardExpiry {
		return new CreditCardExpiry( 9, 2038 );
	}

}
