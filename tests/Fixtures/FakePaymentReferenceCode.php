<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;

class FakePaymentReferenceCode extends PaymentReferenceCode {

	public function __construct() {
		parent::__construct( 'XW', 'DARE99', 'X' );
	}
}
