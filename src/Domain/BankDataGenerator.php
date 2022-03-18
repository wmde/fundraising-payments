<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * TODO: Move the implementation to this context so this interface is not longer orphaned
 */
interface BankDataGenerator {

	public function getBankDataFromAccountData( string $account, string $bankCode ): BankData;

	public function getBankDataFromIban( Iban $iban ): BankData;

}
