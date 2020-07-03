<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Infrastructure;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
