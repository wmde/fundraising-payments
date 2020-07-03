<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Infrastructure;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class FakeCreditCardService implements CreditCardService {

	public function getExpirationDate( string $customerId ): CreditCardExpiry {
		return new CreditCardExpiry( 9, 2038 );
	}

}
