<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class LegacyDataProvider {
	public function __construct(
		private PaymentRepository $repository,
		private BankDataGenerator $bankDataGenerator
	) {
	}

	public function getDataForPayment( int $paymentId ): LegacyPaymentData {
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
			[
				'iban' => $extendedBankData->iban->toString(),
				'bic' => $extendedBankData->bic,
				// "Legacy" also means German field names in this case
				'konto' => $extendedBankData->account,
				'blz' => $extendedBankData->bankCode,
				'bankname' => $extendedBankData->bankName
			]
		);
	}
}
