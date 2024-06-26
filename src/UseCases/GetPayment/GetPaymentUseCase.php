<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\GetPayment;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class GetPaymentUseCase {

	public function __construct(
		private PaymentRepository $repository,
		private BankDataGenerator $bankDataGenerator
	) {
	}

	/**
	 * @param int $paymentId (not a donation ID!)
	 *
	 * @return array<string, scalar>
	 *
	 */
	public function getPaymentDataArray( int $paymentId ): array {
		try {
			$payment = $this->repository->getPaymentById( $paymentId );
		} catch ( PaymentNotFoundException ) {
			throw new \DomainException( sprintf(
				'Payment was not found. This is a domain error, where did you get the payment ID "%d" from?',
				$paymentId
			) );
		}
		$resultArray = $payment->getDisplayValues();
		if ( $payment instanceof DirectDebitPayment && $payment->getIban() !== null ) {
			$resultArray = $this->createExtendedPaymentFieldArray( $payment, $payment->getIban() );
		}

		return $resultArray;
	}

	/**
	 * @param int $paymentId (not a donation ID!)
	 *
	 * @return LegacyPaymentData
	 */
	public function getLegacyPaymentDataObject( int $paymentId ): LegacyPaymentData {
		try {
			$payment = $this->repository->getPaymentById( $paymentId );
		} catch ( PaymentNotFoundException ) {
			throw new \DomainException( sprintf(
				'Payment was not found. This is a domain error, where did you get the payment ID "%d" from?',
				$paymentId
			) );
		}
		$legacyData = $payment->getLegacyData();

		if ( $payment instanceof DirectDebitPayment && $payment->getIban() !== null ) {
			$legacyData = $this->createExtendedLegacyData( $legacyData, $payment->getIban() );
		}

		return $legacyData;
	}

	private function createExtendedLegacyData( LegacyPaymentData $legacyData, Iban $iban ): LegacyPaymentData {
		$extendedBankData = $this->bankDataGenerator->getBankDataFromIban( $iban );
		return new LegacyPaymentData(
			$legacyData->amountInEuroCents,
			$legacyData->intervalInMonths,
			$legacyData->paymentName,
			$this->getLegacyBankdataFieldsArray( $extendedBankData )
		);
	}

	/**
	 * @param Payment $payment
	 * @param Iban $iban
	 *
	 * @return array<string,scalar>
	 */
	private function createExtendedPaymentFieldArray( Payment $payment, Iban $iban ): array {
		$extendedBankData = $this->bankDataGenerator->getBankDataFromIban( $iban );
		return array_merge(
			$payment->getDisplayValues(),
			$this->getLegacyBankdataFieldsArray( $extendedBankData )
		);
	}

	/**
	 * @param ExtendedBankData $extendedBankData
	 *
	 * @return array<string,string>
	 */
	private function getLegacyBankdataFieldsArray( ExtendedBankData $extendedBankData ): array {
		return [
			'iban' => $extendedBankData->iban->toString(),
			'bic' => $extendedBankData->bic,
			// "Legacy" also means German field names in this case
			'konto' => $extendedBankData->account,
			'blz' => $extendedBankData->bankCode,
			'bankname' => $extendedBankData->bankName
		];
	}
}
