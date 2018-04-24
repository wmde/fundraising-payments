<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GenerateIban;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlacklist;
use WMDE\Fundraising\PaymentContext\ResponseModel\IbanResponse;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen <kai.nissen@wikimedia.de>
 */
class GenerateIbanUseCase {

	private $bankDataGenerator;
	private $ibanBlacklist;

	public function __construct( BankDataGenerator $bankDataGenerator, IbanBlacklist $ibanBlacklist ) {
		$this->bankDataGenerator = $bankDataGenerator;
		$this->ibanBlacklist = $ibanBlacklist;
	}

	public function generateIban( GenerateIbanRequest $request ): IbanResponse {
		try {
			$bankData = $this->bankDataGenerator->getBankDataFromAccountData(
				$request->getBankAccount(),
				$request->getBankCode()
			);

			if ( $this->ibanBlacklist->isIbanBlocked( $bankData->getIban() ) ) {
				return IbanResponse::newFailureResponse();
			}
		}
		catch ( \RuntimeException $ex ) {
			return IbanResponse::newFailureResponse();
		}

		return IbanResponse::newSuccessResponse( $bankData );
	}

}
