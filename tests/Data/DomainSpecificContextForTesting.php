<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificContext;

/**
 * Create a generic domain specific object that resembles a donation
 */
class DomainSpecificContextForTesting {
	public static function create(): DomainSpecificContext {
		return new DomainSpecificContext(
			1,
			null,
			'D-1',
			'Hubert J.',
			'Farnsworth'
		);
	}
}
