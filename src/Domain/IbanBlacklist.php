<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @licence GNU GPL v2+
 */
interface IbanBlacklist {

	public function isIbanBlocked( Iban $iban ): bool;

}
