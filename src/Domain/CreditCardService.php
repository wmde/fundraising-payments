<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Tests\Fixtures\CreditCardExpiry;

/**
 * TODO: This might need to be renamed when the mcp service is
 *       imported see https://phabricator.wikimedia.org/T300482
 */
interface CreditCardService {

	/**
	 * @param string $customerId
	 *
	 * @return CreditCardExpiry
	 * @throws CreditCardExpiryFetchingException
	 */
	public function getExpirationDate( string $customerId ): CreditCardExpiry;

}
