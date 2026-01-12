<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;

#[CoversClass( LegacyPayPalURLGeneratorConfig::class )]
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
			$this->createStub( TranslatableDescription::class )
		);
	}

}
