<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GenerateBankData;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;

class GenerateBankDataFromGermanLegacyBankDataUseCase {

	private BankDataGenerator $bankDataGenerator;
	private IbanBlockList $ibanBlockList;

	public function __construct( BankDataGenerator $bankDataGenerator, IbanBlockList $ibanBlockList ) {
		$this->bankDataGenerator = $bankDataGenerator;
		$this->ibanBlockList = $ibanBlockList;
	}

	public function generateIban( string $bankAccount, string $bankCode ): BankDataSuccessResponse|BankDataFailureResponse {
		try {
			$bankData = $this->bankDataGenerator->getBankDataFromAccountData( $bankAccount, $bankCode );
		}
		catch ( \RuntimeException $ex ) {
			return new BankDataFailureResponse( $ex->getMessage() );
		}

		if ( $this->ibanBlockList->isIbanBlocked( $bankData->iban->toString() ) ) {
			return new BankDataFailureResponse( 'IBAN is blocked' );
		}

		return new BankDataSuccessResponse( $bankData );
	}

}
