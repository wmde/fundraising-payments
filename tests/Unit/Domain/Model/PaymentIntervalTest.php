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

	public function testCreatesFromString(): void {
		$this->assertSame( PaymentInterval::OneTime, PaymentInterval::fromString( "OneTime" ) );
		$this->assertSame( PaymentInterval::Monthly, PaymentInterval::fromString( "Monthly" ) );
		$this->assertSame( PaymentInterval::Quarterly, PaymentInterval::fromString( "Quarterly" ) );
		$this->assertSame( PaymentInterval::HalfYearly, PaymentInterval::fromString( "HalfYearly" ) );
		$this->assertSame( PaymentInterval::Yearly, PaymentInterval::fromString( "Yearly" ) );
	}

	public function testGivenNonExistingIntervalFromStringThrowsException(): void {
		$this->expectException( \OutOfBoundsException::class );
		PaymentInterval::fromString( "every blue moon" );
	}
}
