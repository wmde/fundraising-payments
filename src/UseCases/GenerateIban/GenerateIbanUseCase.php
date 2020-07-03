<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GenerateIban;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\ResponseModel\IbanResponse;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen <kai.nissen@wikimedia.de>
 */
class GenerateIbanUseCase {

	private $bankDataGenerator;
	private $ibanBlocklist;

	public function __construct( BankDataGenerator $bankDataGenerator, IbanBlocklist $ibanBlocklist ) {
		$this->bankDataGenerator = $bankDataGenerator;
		$this->ibanBlocklist = $ibanBlocklist;
	}

	public function generateIban( GenerateIbanRequest $request ): IbanResponse {
		try {
			$bankData = $this->bankDataGenerator->getBankDataFromAccountData(
				$request->getBankAccount(),
				$request->getBankCode()
			);
		}
		catch ( \RuntimeException $ex ) {
			return IbanResponse::newFailureResponse();
		}

		if ( $this->ibanBlocklist->isIbanBlocked( $bankData->getIban() ) ) {
			return IbanResponse::newFailureResponse();
		}

		return IbanResponse::newSuccessResponse( $bankData );
	}

}
