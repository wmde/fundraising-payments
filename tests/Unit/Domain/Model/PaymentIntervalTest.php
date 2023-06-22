<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval
 */
class PaymentIntervalTest extends TestCase {
	public function testOneTimeIsNotRecurring(): void {
		$interval = PaymentInterval::OneTime;
		$this->assertFalse( $interval->isRecurring() );
	}

	/**
	 * @dataProvider recurringIntervals
	 */
	public function testRecurring( PaymentInterval $interval ): void {
		$this->assertTrue( $interval->isRecurring() );
	}

	/**
	 * @return iterable<array{PaymentInterval}>
	 */
	public static function recurringIntervals(): iterable {
		yield [ PaymentInterval::Monthly ];
		yield [ PaymentInterval::Quarterly ];
		yield [ PaymentInterval::HalfYearly ];
		yield [ PaymentInterval::Yearly ];
	}
}
