<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan
 */
class SubscriptionPlanTest extends TestCase {
	public function testToJSONSerialization(): void {
		$plan = new SubscriptionPlan( 'Monthly Membership Payment', 'membership-2023', 1, null, 'Membership Payment, billed monthly' );

		$serializedPlan = json_decode( $plan->toJSON(), true, 512, JSON_THROW_ON_ERROR );

		$this->assertSame(
			[
				'name' => 'Monthly Membership Payment',
				'product_id' => 'membership-2023',
				'description' => 'Membership Payment, billed monthly',
				'billing_cycles' => [ [
					'sequence' => 0,
					'tenure_type' => 'REGULAR',
					'frequency' => [
						'interval_unit' => 'MONTH',
						'interval_count' => 1
					],
					'total_cycles' => 0
				] ],
				'payment_preferences' => [
					'auto_bill_outstanding' => true,
					'setup_fee_failure_action' => 'CONTINUE',
					'payment_failure_threshold' => 0,
					'setup_fee' => [
						'currency_code' => 'EUR',
						'value' => '0'
					]
				]
			],
			$serializedPlan
		);
	}

	public function testCreateFromJSON(): void {
		$plan = SubscriptionPlan::createFromJSON( [
				'id' => 'FAKE_GENERATED_ID',
				'name' => 'Yearly Membership Payment',
				'product_id' => 'membership-2023',
				'description' => 'Membership Payment, billed yearly',
				'billing_cycles' => [ [
					'sequence' => 0,
					'tenure_type' => 'REGULAR',
					'frequency' => [
						'interval_unit' => 'MONTH',
						'interval_count' => 12
					],
					'total_cycles' => 0
				] ],
				'payment_preferences' => [
					'auto_bill_outstanding' => true,
					'setup_fee_failure_action' => 'CONTINUE',
					'payment_failure_threshold' => 0,
					'setup_fee' => [
						'currency_code' => 'EUR',
						'value' => '0'
					]
				]
		] );

		$this->assertSame( 'Yearly Membership Payment', $plan->name );
		$this->assertSame( 'FAKE_GENERATED_ID', $plan->id );
		$this->assertSame( 'membership-2023', $plan->productId );
		$this->assertSame( 'Membership Payment, billed yearly', $plan->description );
		$this->assertSame( 12, $plan->monthlyInterval );
	}

	/**
	 * @dataProvider brokenBillingCycleDataProvider
	 */
	public function testCreateFromJSONFailsOnReadingIntervalFromBillingCycle( mixed $testBillingCycleValues, string $exptectedExceptionmessage ): void {
		$this->expectExceptionMessage( $exptectedExceptionmessage );
		$plan = SubscriptionPlan::createFromJSON( [
			'id' => 'FAKE_GENERATED_ID',
			'name' => 'Yearly Membership Payment',
			'product_id' => 'membership-2023',
			'description' => 'Membership Payment, billed yearly',
			'billing_cycles' => $testBillingCycleValues,
			'payment_preferences' => [
				'auto_bill_outstanding' => true,
				'setup_fee_failure_action' => 'CONTINUE',
				'payment_failure_threshold' => 0,
				'setup_fee' => [
					'currency_code' => 'EUR',
					'value' => '0'
				]
			]
		] );
	}

	public static function brokenBillingCycleDataProvider(): iterable {
		yield 'passing null' => [ null, 'Wrong billing cycle data' ];
		yield 'passing a string' => [ 'hallo', 'Wrong billing cycle data' ];
		yield 'empty billing cycles' => [ [], 'Wrong billing cycle data' ];
		yield 'too many billing cycles' => [
			[ [
			'sequence' => 0,
			'tenure_type' => 'REGULAR',
			'frequency' => [
				'interval_unit' => 'MONTH',
				'interval_count' => 12
			],
			'total_cycles' => 0
			],
			[
				'sequence' => 1,
				'tenure_type' => 'REGULAR',
				'frequency' => [
					'interval_unit' => 'MONTH',
					'interval_count' => 12
				],
				'total_cycles' => 0
			] ],
			'Wrong billing cycle data'
		];

		yield 'missing "interval_count" field' => [
			[ [
				'sequence' => 0,
				'tenure_type' => 'REGULAR',
				'frequency' => [
					'interval_unit' => 'MONTH',
				],
				'total_cycles' => 0
			] ],
			'Wrong frequency data in billing cycle'
		];

		yield 'missing "frequency" field' => [
			[ [
				'sequence' => 0,
				'tenure_type' => 'REGULAR',
				'total_cycles' => 0
			] ],
			'Wrong frequency data in billing cycle'
		];

		yield '"interval_unit" field is not set to MONTH' => [
			[ [
				'sequence' => 0,
				'tenure_type' => 'REGULAR',
				'frequency' => [
					'interval_unit' => 'DAY',
					'interval_count' => 12
				],
				'total_cycles' => 0
			] ],
			'interval_unit must be MONTH'
		];
	}
}
