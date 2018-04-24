<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\IbanBlacklist;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FailingIbanBlacklist implements IbanBlacklist {

	public function isIbanBlocked( Iban $iban ): bool {
		return true;
	}

}
