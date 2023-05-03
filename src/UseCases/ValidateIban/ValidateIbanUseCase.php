<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\ValidateIban;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataFailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataSuccessResponse;

class ValidateIbanUseCase {

	public function __construct(
		private IbanBlockList $ibanBlockList,
		private BankDataGenerator $bankDataGenerator
	) {
	}

	public function ibanIsValid( string $iban ): BankDataSuccessResponse|BankDataFailureResponse {
		try {
			$bankData = $this->bankDataGenerator->getBankDataFromIban( new Iban( $iban ) );
		} catch ( \InvalidArgumentException $ex ) {
			return new BankDataFailureResponse( $ex->getMessage() );
		}

		if ( $this->ibanBlockList->isIbanBlocked( $bankData->iban->toString() ) ) {
			return new BankDataFailureResponse( 'IBAN is blocked' );
		}

		return new BankDataSuccessResponse( $bankData );
	}

}
