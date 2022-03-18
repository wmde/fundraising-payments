<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\UseCases\IbanResponse;

class GenerateBankDataFromGermanLegacyBankDataUseCase {

	private BankDataGenerator $bankDataGenerator;
	private IbanBlocklist $ibanBlocklist;

	public function __construct( BankDataGenerator $bankDataGenerator, IbanBlocklist $ibanBlocklist ) {
		$this->bankDataGenerator = $bankDataGenerator;
		$this->ibanBlocklist = $ibanBlocklist;
	}

	public function generateIban( string $bankAccount, string $bankCode ): IbanResponse {
		try {
			$bankData = $this->bankDataGenerator->getBankDataFromAccountData( $bankAccount, $bankCode );
		}
		catch ( \RuntimeException $ex ) {
			return IbanResponse::newFailureResponse();
		}

		if ( $this->ibanBlocklist->isIbanBlocked( $bankData->iban ) ) {
			return IbanResponse::newFailureResponse();
		}

		return IbanResponse::newSuccessResponse( $bankData );
	}

}
