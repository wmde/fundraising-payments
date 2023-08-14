<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig
 */
class PayPalURLGeneratorConfigTest extends TestCase {

	public function testGivenIncompletePayPalUrlConfig_exceptionIsThrown(): void {
		$this->expectException( \RuntimeException::class );
		$this->newIncompletePayPalUrlConfig();
	}

	private function newIncompletePayPalUrlConfig(): LegacyPayPalURLGeneratorConfig {
		return LegacyPayPalURLGeneratorConfig::newFromConfig(
			[
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_BASE_URL => 'http://that.paymentprovider.com/?',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_LOCALE => 'de_DE',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_ACCOUNT_ADDRESS => 'some@email-adress.com',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_NOTIFY_URL => 'http://my.donation.app/handler/paypal/',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_RETURN_URL => 'http://my.donation.app/donation/confirm/',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_CANCEL_URL => '',
			],
			$this->createMock( TranslatableDescription::class )
		);
	}

	public function testGivenValidPayPalUrlConfig_payPalConfigIsReturned(): void {
		$this->assertInstanceOf( LegacyPayPalURLGeneratorConfig::class, $this->newPayPalUrlConfig() );
	}

	private function newPayPalUrlConfig(): LegacyPayPalURLGeneratorConfig {
		return LegacyPayPalURLGeneratorConfig::newFromConfig(
			[
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_BASE_URL => 'http://that.paymentprovider.com/?',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_LOCALE => 'de_DE',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_ACCOUNT_ADDRESS => 'some@email-adress.com',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_NOTIFY_URL => 'http://my.donation.app/handler/paypal/',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_RETURN_URL => 'http://my.donation.app/donation/confirm/',
				LegacyPayPalURLGeneratorConfig::CONFIG_KEY_CANCEL_URL => 'http://my.donation.app/donation/cancel/',
			],
			$this->createMock( TranslatableDescription::class )
		);
	}

}
