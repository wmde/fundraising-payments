<?php

declare( strict_types = 1 );

namespace Unit\Services\PayPal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfigFactory;

#[CoversClass( PayPalPaymentProviderAdapterConfigFactory::class )]
class PayPalPaymentProviderAdapterConfigFactoryTest extends TestCase {
	public function testCreateConfigForProductAndLanguage(): void {
		$config = PayPalPaymentProviderAdapterConfigFactory::createConfig( $this->givenConfig(), 'donation', 'en' );

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

	public function testWhenProductKeyDoesNotExistAnExceptionIsThrown(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( "'membership' does not exist in PayPal API configuration. Please check your configuration file." );

		PayPalPaymentProviderAdapterConfigFactory::createConfig( $this->givenConfig(), 'membership', 'en' );
	}

	public function testWhenLanguageKeyDoesNotExistAnExceptionIsThrown(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( "'de' does not exist in PayPal API configuration for product 'donation'. Please check your configuration file." );

		PayPalPaymentProviderAdapterConfigFactory::createConfig( $this->givenConfig(), 'donation', 'de' );
	}

	/**
	 * @phpstan-ignore-next-line
	 */
	private function givenConfig(): array {
		return [
			'donation' => [
				// no 'de' language to test error checking
				'en' => [
					'product_id' => 'paypal_product_id_1',
					'product_name' => 'Donation',
					'return_url' => 'https://example.com/return',
					'cancel_url' => 'https://example.com/cancel',
					'subscription_plans' => [
						[
							'id' => 'F00',
							'name' => 'Monthly donation',
							'interval' => 'Monthly'
						],
						[
							'id' => 'F11',
							'name' => 'Yearly donation',
							'interval' => 'Yearly'
						]
					]
				]
			],
			// no memberships here to allow reuse of this fixture for error checking tests
		];
	}
}
