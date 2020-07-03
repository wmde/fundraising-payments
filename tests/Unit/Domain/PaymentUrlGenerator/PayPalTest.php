<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPal as PaypalUrlGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalConfig;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPal
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class PayPalTest extends \PHPUnit\Framework\TestCase {

	private const BASE_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
	private const ACCOUNT_ADDRESS = 'foerderpp@wikimedia.de';
	private const NOTIFY_URL = 'http://my.donation.app/handler/paypal/';
	private const RETURN_URL = 'http://my.donation.app/donation/confirm/';
	private const CANCEL_URL = 'http://my.donation.app/donation/cancel/';
	private const ITEM_NAME = 'Mentioning that awesome organization on the invoice';

	public function testSubscriptions(): void {
		$generator = new PayPalUrlGenerator( $this->newPayPalUrlConfig(), self::ITEM_NAME );

		$this->assertUrlValidForSubscriptions(
			$generator->generateUrl( 1234, Euro::newFromString( '12.34' ), 3, 'utoken', 'atoken' )
		);
	}

	private function assertUrlValidForSubscriptions( string $generatedUrl ): void {
		$this->assertCommonParamsSet( $generatedUrl );
		$this->assertSubscriptionRelatedParamsSet( $generatedUrl );
	}

	public function testSinglePayments(): void {
		$generator = new PayPalUrlGenerator( $this->newPayPalUrlConfig(), self::ITEM_NAME );

		$this->assertUrlValidForSinglePayments(
			$generator->generateUrl( 1234, Euro::newFromString( '12.34' ), 0, 'utoken', 'atoken' )
		);
	}

	private function assertUrlValidForSinglePayments( string $generatedUrl ): void {
		$this->assertCommonParamsSet( $generatedUrl );
		$this->assertSinglePaymentRelatedParamsSet( $generatedUrl );
	}

	private function newPayPalUrlConfig(): PayPalConfig {
		return PayPalConfig::newFromConfig(
			[
				'base-url' => self::BASE_URL,
				'account-address' => self::ACCOUNT_ADDRESS,
				'notify-url' => self::NOTIFY_URL,
				'return-url' => self::RETURN_URL,
				'cancel-url' => self::CANCEL_URL
			]
		);
	}

	public function testGivenIncompletePayPalUrlConfig_exceptionIsThrown(): void {
		$this->expectException( \RuntimeException::class );
		$this->newIncompletePayPalUrlConfig();
	}

	private function newIncompletePayPalUrlConfig(): PayPalConfig {
		return PayPalConfig::newFromConfig(
			[
				'base-url' => self::BASE_URL,
				'account-address' => 'some@email-adress.com',
				'notify-url' => self::NOTIFY_URL,
				'return-url' => self::RETURN_URL,
				'cancel-url' => ''
			]
		);
	}

	public function testDelayedSubscriptions(): void {
		$generator = new PayPalUrlGenerator( $this->newPayPalUrlConfigWithDelayedPayment(), self::ITEM_NAME );

		$this->assertUrlValidForDelayedSubscriptions(
			$generator->generateUrl( 1234, Euro::newFromString( '12.34' ), 3, 'utoken', 'atoken' )
		);
	}

	private function assertUrlValidForDelayedSubscriptions( string $generatedUrl ): void {
		$this->assertCommonParamsSet( $generatedUrl );
		$this->assertSubscriptionRelatedParamsSet( $generatedUrl );
		$this->assertTrialPeriodRelatedParametersSet( $generatedUrl );
	}

	private function newPayPalUrlConfigWithDelayedPayment(): PayPalConfig {
		return PayPalConfig::newFromConfig(
			[
				'base-url' => self::BASE_URL,
				'account-address' => self::ACCOUNT_ADDRESS,
				'notify-url' => self::NOTIFY_URL,
				'return-url' => self::RETURN_URL,
				'cancel-url' => self::CANCEL_URL,
				'delay-in-days' => 90
			]
		);
	}

	private function assertCommonParamsSet( string $generatedUrl ): void {
		$this->assertStringContainsString( 'https://www.sandbox.paypal.com/cgi-bin/webscr', $generatedUrl );
		$this->assertStringContainsString( 'business=foerderpp%40wikimedia.de', $generatedUrl );
		$this->assertStringContainsString( 'currency_code=EUR', $generatedUrl );
		$this->assertStringContainsString( 'lc=de_DE', $generatedUrl );
		$this->assertStringContainsString( 'item_name=Mentioning+that+awesome+organization+on+the+invoice', $generatedUrl );
		$this->assertStringContainsString( 'item_number=1234', $generatedUrl );
		$this->assertStringContainsString( 'notify_url=http%3A%2F%2Fmy.donation.app%2Fhandler%2Fpaypal%2F', $generatedUrl );
		$this->assertStringContainsString( 'cancel_return=http%3A%2F%2Fmy.donation.app%2Fdonation%2Fcancel%2F', $generatedUrl );
		$this->assertStringContainsString(
			'return=http%3A%2F%2Fmy.donation.app%2Fdonation%2Fconfirm%2F%3Fid%3D1234%26accessToken%3Datoken',
			$generatedUrl
		);
		$this->assertStringContainsString( 'custom=%7B%22sid%22%3A1234%2C%22utoken%22%3A%22utoken%22%7D', $generatedUrl );
	}

	private function assertSinglePaymentRelatedParamsSet( string $generatedUrl ): void {
		$this->assertStringContainsString( 'cmd=_donations', $generatedUrl );
		$this->assertStringContainsString( 'amount=12.34', $generatedUrl );
	}

	private function assertTrialPeriodRelatedParametersSet( string $generatedUrl ): void {
		$this->assertStringContainsString( 'a1=0', $generatedUrl );
		$this->assertStringContainsString( 'p1=90', $generatedUrl );
		$this->assertStringContainsString( 't1=D', $generatedUrl );
	}

	private function assertSubscriptionRelatedParamsSet( string $generatedUrl ): void {
		$this->assertStringContainsString( 'cmd=_xclick-subscriptions', $generatedUrl );
		$this->assertStringContainsString( 'no_shipping=1', $generatedUrl );
		$this->assertStringContainsString( 'src=1', $generatedUrl );
		$this->assertStringContainsString( 'sra=1', $generatedUrl );
		$this->assertStringContainsString( 'srt=0', $generatedUrl );
		$this->assertStringContainsString( 'a3=12.34', $generatedUrl );
		$this->assertStringContainsString( 'p3=3', $generatedUrl );
		$this->assertStringContainsString( 't3=M', $generatedUrl );
	}

}
