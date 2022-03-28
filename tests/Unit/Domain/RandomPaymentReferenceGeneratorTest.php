<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\RandomPaymentReferenceGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\RandomPaymentReferenceGenerator
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator
 */
class RandomPaymentReferenceGeneratorTest extends TestCase {
	private const NUM_RANDOM_SAMPLES = 1000;

	/**
	 * This test creates random codes to assert that they are not invalid (which would throw an exception)
	 *
	 * @see https://github.com/sebastianbergmann/phpunit/issues/3016
	 *
	 * @return void
	 */
	public function testRandomGeneratorProducesValidCodes(): void {
		$generator = new RandomPaymentReferenceGenerator();
		$exception = null;

		try {
			for ( $i = 0; $i < self::NUM_RANDOM_SAMPLES; $i++ ) {
				$generator->newPaymentReference( 'XD' );
			}
		} catch ( \UnexpectedValueException $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception, 'Generated random codes must not throw exception' );
	}
}
