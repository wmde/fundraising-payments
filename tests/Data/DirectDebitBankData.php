<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

class DirectDebitBankData {

	public const IBAN = 'DE02701500000000594937';
	public const BIC = 'SSKMDEMMXXX';
	public const BANK_NAME = 'Stadtsparkasse München';
	public const BANK_CODE = '70150000';
	public const ACCOUNT = '0000594937';

	public static function validBankData(): ExtendedBankData {
		return new ExtendedBankData(
			new Iban( self::IBAN ),
			self::BIC,
			self::ACCOUNT,
			self::BANK_CODE,
			self::BANK_NAME
		);
	}

}
