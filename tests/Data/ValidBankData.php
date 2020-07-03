<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ValidBankData extends BankData {

	public const IBAN = 'DE00123456789012345678';
	public const BIC = 'SCROUSDBXXX';
	public const BANK_NAME = 'Scrooge Bank';
	public const BANK_CODE = '12345678';
	public const ACCOUNT = '1234567890';

	public function __construct() {
		$this->setIban( new Iban( self::IBAN ) )
			->setBic( self::BIC )
			->setBankName( self::BANK_NAME )
			->setBankCode( self::BANK_CODE )
			->setAccount( self::ACCOUNT );

		$this->assertNoNullFields()->freeze();
	}

}
