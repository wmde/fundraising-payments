<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalAPIConfigFactory;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class PayPalAPIConfigFactoryTest extends TestCase {
	public function testCreateConfigForProductAndLanguage(): void {
		$config = PayPalAPIConfigFactory::createConfig( $this->givenConfig(), 'donation', 'en' );

		$this->assertSame( 'Donation', $config->productName );
		$this->assertSame( 'https://example.com/return', $config->returnURL );
		$this->assertSame( 'https://example.com/cancel', $config->cancelURL );
		$this->assertCount( 2, $config->subscriptionPlanMap );
		$this->assertArrayHasKey( PaymentInterval::Monthly->name, $config->subscriptionPlanMap );
		$this->assertEquals(
			new SubscriptionPlan(
				'Monthly donation',
				'paypal_product_id_1',
				PaymentInterval::Monthly,
				'F00'
			),
			$config->subscriptionPlanMap[PaymentInterval::Monthly->name]
		);
		// TODO check yearly plan
	}

	/**
	 * @phpstan-ignore-next-line
	 */
	private function givenConfig(): array {
		return [
			'paypal_api' => [
				'donation' => [
					'return_url' => 'https://example.com/return',
					'cancel_url' => 'https://example.com/cancel',
					'de' => [
						// left empty on purpose for error checking
					],
					'en' => [
						'product_id' => 'paypal_product_id_1',
						'product_name' => 'Donation',
						'plans' => [
							[
								'id' => 'F00',
								'name' => 'Monthly donation',
								'interval' => 1
							],
							[
								'id' => 'F11',
								'name' => 'Yearly donation',
								'interval' => 12
							]
						]
					]
				],
				'membership' => [
					// left empty on purpose for error checking
				]
			]
		];
	}
}
