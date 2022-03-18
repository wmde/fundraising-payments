<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

class DirectDebitBankData {

	public const IBAN = 'DE00123456789012345678';
	public const BIC = 'SCROUSDBXXX';
	public const BANK_NAME = 'Scrooge Bank';
	public const BANK_CODE = '12345678';
	public const ACCOUNT = '1234567890';

	public static function validBankData(): BankData {
		return new BankData(
			new Iban( self::IBAN ),
			self::BIC,
			self::ACCOUNT,
			self::BANK_CODE,
			self::BANK_NAME
		);
	}

}
