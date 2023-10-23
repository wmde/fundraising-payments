<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

class TestIban extends Iban {
	public function __construct() {
		parent::__construct( 'DE12500105170648489890' );
	}
}
