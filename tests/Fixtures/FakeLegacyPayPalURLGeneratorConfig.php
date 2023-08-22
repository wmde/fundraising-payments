<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;

class FakeLegacyPayPalURLGeneratorConfig extends LegacyPayPalURLGeneratorConfig {
	private const BASE_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
	private const LOCALE = 'de_DE';
	private const ACCOUNT_ADDRESS = 'foerderpp@wikimedia.de';
	private const NOTIFY_URL = 'https://my.donation.app/handler/paypal/';
	private const RETURN_URL = 'https://my.donation.app/donation/confirm/';
	private const CANCEL_URL = 'https://my.donation.app/donation/cancel/';
	private const ITEM_NAME = 'Mentioning that awesome organization on the invoice';

	public static function create(): LegacyPayPalURLGeneratorConfig {
		return LegacyPayPalURLGeneratorConfig::newFromConfig(
			[
				'base-url' => self::BASE_URL,
				'locale' => self::LOCALE,
				'account-address' => self::ACCOUNT_ADDRESS,
				'notify-url' => self::NOTIFY_URL,
				'return-url' => self::RETURN_URL,
				'cancel-url' => self::CANCEL_URL
			],
			new class( self::ITEM_NAME ) implements TranslatableDescription {
				public function __construct( private readonly string $itemName ) {
				}

				public function getText( Euro $paymentAmount, PaymentInterval $paymentInterval ): string {
					return $this->itemName;
				}
			}
		);
	}
}
