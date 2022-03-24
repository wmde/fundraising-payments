<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\ValidateIban;

use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;

class ValidateIbanUseCase {

	public function __construct(
		private IbanValidator $ibanValidator,
		private IbanBlockList $ibanBlockList
	) {
	}

	public function ibanIsValid( string $iban ): bool {
		return !$this->ibanBlockList->isIbanBlocked( $iban ) &&
			$this->ibanValidator->validate( $iban )->isSuccessful();
	}

}
