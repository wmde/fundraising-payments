<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalSubscription;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalSubscription
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPaymentIdentifier
 */
class PayPalSubscriptionTest extends TestCase {
	private const SUBSCRIPTION_ID = 'I-BW452GLLEP1G';

	public function testConstructorAllowsOnlyForRecurringPayment(): void {
		$this->expectException( \DomainException::class );

		new PayPalSubscription( new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime ), self::SUBSCRIPTION_ID );
	}

	/**
	 * @dataProvider provideEmptySubscriptionIDs
	 */
	public function testConstructorEnforcesNonEmptySubscriptionId( string $subscriptionId ): void {
		$this->expectException( \DomainException::class );

		new PayPalSubscription( $this->givenMonthlyPayment(), $subscriptionId );
	}

	/**
	 * @return iterable<array{string}>
	 */
	public static function provideEmptySubscriptionIDs(): iterable {
		yield 'empty string' => [ '' ];
		yield 'string consisting of spaces' => [ '   ' ];
		yield 'string consisting of whitespace characters' => [ "\t\r\n " ];
	}

	public function testConstructorSetsProperties(): void {
		$payment = $this->givenMonthlyPayment();
		$subscription = new PayPalSubscription( $payment, self::SUBSCRIPTION_ID );

		$this->assertSame( $payment, $subscription->getPayment() );
		$this->assertSame( self::SUBSCRIPTION_ID, $subscription->getSubscriptionId() );
	}

	private function givenMonthlyPayment(): PayPalPayment {
		return new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly );
	}
}
