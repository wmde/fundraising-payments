<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\TranslatableDescription;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalConfig
 */
class PayPalConfigTest extends TestCase {

	public function testGivenIncompletePayPalUrlConfig_exceptionIsThrown(): void {
		$this->expectException( \RuntimeException::class );
		$this->newIncompletePayPalUrlConfig();
	}

	private function newIncompletePayPalUrlConfig(): PayPalConfig {
		return PayPalConfig::newFromConfig(
			[
				PayPalConfig::CONFIG_KEY_BASE_URL => 'http://that.paymentprovider.com/?',
				PayPalConfig::CONFIG_KEY_LOCALE => 'de_DE',
				PayPalConfig::CONFIG_KEY_ACCOUNT_ADDRESS => 'some@email-adress.com',
				PayPalConfig::CONFIG_KEY_NOTIFY_URL => 'http://my.donation.app/handler/paypal/',
				PayPalConfig::CONFIG_KEY_RETURN_URL => 'http://my.donation.app/donation/confirm/',
				PayPalConfig::CONFIG_KEY_CANCEL_URL => '',
			],
			$this->createMock( TranslatableDescription::class )
		);
	}

	public function testGivenValidPayPalUrlConfig_payPalConfigIsReturned(): void {
		$this->assertInstanceOf( PayPalConfig::class, $this->newPayPalUrlConfig() );
	}

	private function newPayPalUrlConfig(): PayPalConfig {
		return PayPalConfig::newFromConfig(
			[
				PayPalConfig::CONFIG_KEY_BASE_URL => 'http://that.paymentprovider.com/?',
				PayPalConfig::CONFIG_KEY_LOCALE => 'de_DE',
				PayPalConfig::CONFIG_KEY_ACCOUNT_ADDRESS => 'some@email-adress.com',
				PayPalConfig::CONFIG_KEY_NOTIFY_URL => 'http://my.donation.app/handler/paypal/',
				PayPalConfig::CONFIG_KEY_RETURN_URL => 'http://my.donation.app/donation/confirm/',
				PayPalConfig::CONFIG_KEY_CANCEL_URL => 'http://my.donation.app/donation/cancel/',
			],
			$this->createMock( TranslatableDescription::class )
		);
	}

}
