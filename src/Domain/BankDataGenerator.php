<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

interface BankDataGenerator {

	public function getBankDataFromAccountData( string $account, string $bankCode ): ExtendedBankData;

	public function getBankDataFromIban( Iban $iban ): ExtendedBankData;

}
