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
		$plan = new SubscriptionPlan( 'Monthly Membership Payment', 'membership-2023', 1, 'Membership Payment, billed monthly' );

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
}
