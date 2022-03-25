<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\RandomPaymentReferenceGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\RandomPaymentReferenceGenerator
 */
class RandomPaymentReferenceGeneratorTest extends TestCase {
	private const NUM_RANDOM_SAMPLES = 1000;

	/**
	 * This test creates random codes to assert that they are not invalid (which would throw an exception)
	 *
	 * @return void
	 *
	 * @doesNotPerformAssertions
	 */
	public function testRandomGeneratorProducesValidCodes(): void {
		$generator = new RandomPaymentReferenceGenerator();
		for ( $i = 0; $i < self::NUM_RANDOM_SAMPLES; $i++ ) {
			$generator->newPaymentReference( 'XD' );
		}
	}
}
