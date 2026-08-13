<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

class DirectDebitBankData {

	public const string IBAN = 'DE02701500000000594937';
	public const string BIC = 'SSKMDEMMXXX';
	public const string BANK_NAME = 'Stadtsparkasse München';
	public const string BANK_CODE = '70150000';
	public const string ACCOUNT = '0000594937';

	public const string IRISH_IBAN = 'IE29AIBK93115212345678';
	public const string IRISH_BIC = 'BOFIIE2D';
	public const string INVALID_IRISH_IBAN = 'IE29AIBK931152123456781';

	public static function validBankData(): ExtendedBankData {
		return new ExtendedBankData(
			new Iban( self::IBAN ),
			self::BIC,
			self::ACCOUNT,
			self::BANK_CODE,
			self::BANK_NAME
		);
	}

	public static function validIrishBankData(): ExtendedBankData {
		return new ExtendedBankData(
			new Iban( self::IRISH_IBAN ),
			self::IRISH_BIC,
			'',
			'',
			''
		);
	}

}
