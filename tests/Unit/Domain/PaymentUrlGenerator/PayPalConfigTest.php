<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\LegacyPayPalConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\TranslatableDescription;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\LegacyPayPalConfig
 */
class PayPalConfigTest extends TestCase {

	public function testGivenIncompletePayPalUrlConfig_exceptionIsThrown(): void {
		$this->expectException( \RuntimeException::class );
		$this->newIncompletePayPalUrlConfig();
	}

	private function newIncompletePayPalUrlConfig(): LegacyPayPalConfig {
		return LegacyPayPalConfig::newFromConfig(
			[
				LegacyPayPalConfig::CONFIG_KEY_BASE_URL => 'http://that.paymentprovider.com/?',
				LegacyPayPalConfig::CONFIG_KEY_LOCALE => 'de_DE',
				LegacyPayPalConfig::CONFIG_KEY_ACCOUNT_ADDRESS => 'some@email-adress.com',
				LegacyPayPalConfig::CONFIG_KEY_NOTIFY_URL => 'http://my.donation.app/handler/paypal/',
				LegacyPayPalConfig::CONFIG_KEY_RETURN_URL => 'http://my.donation.app/donation/confirm/',
				LegacyPayPalConfig::CONFIG_KEY_CANCEL_URL => '',
			],
			$this->createMock( TranslatableDescription::class )
		);
	}

	public function testGivenValidPayPalUrlConfig_payPalConfigIsReturned(): void {
		$this->assertInstanceOf( LegacyPayPalConfig::class, $this->newPayPalUrlConfig() );
	}

	private function newPayPalUrlConfig(): LegacyPayPalConfig {
		return LegacyPayPalConfig::newFromConfig(
			[
				LegacyPayPalConfig::CONFIG_KEY_BASE_URL => 'http://that.paymentprovider.com/?',
				LegacyPayPalConfig::CONFIG_KEY_LOCALE => 'de_DE',
				LegacyPayPalConfig::CONFIG_KEY_ACCOUNT_ADDRESS => 'some@email-adress.com',
				LegacyPayPalConfig::CONFIG_KEY_NOTIFY_URL => 'http://my.donation.app/handler/paypal/',
				LegacyPayPalConfig::CONFIG_KEY_RETURN_URL => 'http://my.donation.app/donation/confirm/',
				LegacyPayPalConfig::CONFIG_KEY_CANCEL_URL => 'http://my.donation.app/donation/cancel/',
			],
			$this->createMock( TranslatableDescription::class )
		);
	}

}
