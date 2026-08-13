<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\ValidateIban;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataFailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataSuccessResponse;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\BankDataValidator;

class ValidateIbanUseCase {

	public function __construct(
		private readonly IbanBlockList $ibanBlockList,
		private readonly BankDataGenerator $bankDataGenerator,
		private readonly BankDataValidator $bankDataValidator
	) {
	}

	public function ibanIsValid( string $iban, string $bic = '' ): BankDataSuccessResponse|BankDataFailureResponse {
		if ( $this->isGermanIban( $iban ) ) {
			return $this->validateGermanIban( $iban );
		} else {
			return $this->validateOtherIban( $iban, $bic );
		}
	}

	private function validateGermanIban( string $iban ): BankDataSuccessResponse|BankDataFailureResponse {
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

	private function validateOtherIban( string $iban, string $bic ): BankDataSuccessResponse|BankDataFailureResponse {
		$validationResult = $this->bankDataValidator->validate( $iban, $bic );

		if ( $validationResult->isSuccessful() ) {
			return new BankDataSuccessResponse( new ExtendedBankData(
				new Iban( $iban ),
				$bic,
				'',
				'',
				''
			) );
		} else {
			return new BankDataFailureResponse( $this->getViolations( $validationResult ) );
		}
	}

	private function isGermanIban( string $iban ): bool {
		return str_starts_with( strtoupper( $iban ), 'DE' );
	}

	/**
	 * @param ValidationResult $validationResult
	 *
	 * @return string
	 */
	private function getViolations( ValidationResult $validationResult ): string {
		$messages = [];
		foreach ( $validationResult->getViolations() as $violation ) {
			$messages[] = $violation->getMessageIdentifier();
		}

		return implode( '|', $messages );
	}
}
