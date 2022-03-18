<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CheckIban;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\UseCases\IbanResponse;

class CheckIbanUseCase {

	private BankDataGenerator $bankDataGenerator;
	private IbanBlocklist $ibanBlocklist;
	private IbanValidator $ibanValidator;

	public function __construct(
		BankDataGenerator $bankDataGenerator,
		IbanValidator $ibanValidator,
		IbanBlocklist $blocklist
	) {
		$this->bankDataGenerator = $bankDataGenerator;
		$this->ibanValidator = $ibanValidator;
		$this->ibanBlocklist = $blocklist;
	}

	public function checkIban( Iban $iban ): IbanResponse {
		if ( $this->ibanBlocklist->isIbanBlocked( $iban ) ) {
			return IbanResponse::newFailureResponse();
		}
		if ( !$this->ibanValidator->validate( $iban )->isSuccessful() ) {
			return IbanResponse::newFailureResponse();
		}

		return IbanResponse::newSuccessResponse(
			$this->bankDataGenerator->getBankDataFromIban( $iban )
		);
	}

}
