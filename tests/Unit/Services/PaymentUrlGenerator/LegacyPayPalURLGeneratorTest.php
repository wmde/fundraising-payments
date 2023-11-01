<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeLegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGenerator
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig
 *
 */
class LegacyPayPalURLGeneratorTest extends TestCase {

	private const BASE_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
	private const LOCALE = 'de_DE';
	private const ACCOUNT_ADDRESS = 'foerderpp@wikimedia.de';
	private const NOTIFY_URL = 'https://my.donation.app/handler/paypal/';
	private const RETURN_URL = 'https://my.donation.app/donation/confirm/';
	private const CANCEL_URL = 'https://my.donation.app/donation/cancel/';
	private DomainSpecificContext $testRequestContext;

	public function setup(): void {
		$this->testRequestContext = new DomainSpecificContext(
			1234,
			null,
			'd1234',
			'utoken',
			'atoken'
		);
	}

	public function testSubscriptions(): void {
		$payment = new PayPalPayment(
			1234,
			Euro::newFromString( '12.34' ),
			PaymentInterval::Quarterly
		);
		$generator = new LegacyPayPalURLGenerator(
			FakeLegacyPayPalURLGeneratorConfig::create(),
			new FakeUrlAuthenticator(), $payment
		);

		$this->assertUrlValidForSubscriptions(
			$generator->generateUrl( $this->testRequestContext )
		);
	}

	private function assertUrlValidForSubscriptions( string $generatedUrl ): void {
		$this->assertCommonParamsSet( $generatedUrl );
		$this->assertSubscriptionRelatedParamsSet( $generatedUrl );
	}

	public function testSinglePayments(): void {
		$payment = new PayPalPayment(
			1234,
			Euro::newFromString( '12.34' ),
			PaymentInterval::OneTime
		);
		$generator = new LegacyPayPalURLGenerator( FakeLegacyPayPalURLGeneratorConfig::create(), new FakeUrlAuthenticator(), $payment );

		$this->assertUrlValidForSinglePayments(
			$generator->generateUrl( $this->testRequestContext )
		);
	}

	private function assertUrlValidForSinglePayments( string $generatedUrl ): void {
		$this->assertCommonParamsSet( $generatedUrl );
		$this->assertSinglePaymentRelatedParamsSet( $generatedUrl );
	}

	public function testGivenIncompletePayPalUrlConfig_exceptionIsThrown(): void {
		$this->expectException( \RuntimeException::class );
		$this->newIncompletePayPalUrlConfig();
	}

	private function newIncompletePayPalUrlConfig(): LegacyPayPalURLGeneratorConfig {
		$descriptionStub = $this->createStub( TranslatableDescription::class );
		return LegacyPayPalURLGeneratorConfig::newFromConfig(
			[
				'base-url' => self::BASE_URL,
				'locale' => self::LOCALE,
				'account-address' => 'some@email-adress.com',
				'notify-url' => self::NOTIFY_URL,
				'return-url' => self::RETURN_URL,
				'cancel-url' => ''
			],
			$descriptionStub
		);
	}

	public function testDelayedSubscriptions(): void {
		$payment = new PayPalPayment(
			1234,
			Euro::newFromString( '12.34' ),
			PaymentInterval::Quarterly
		);

		$generator = new LegacyPayPalURLGenerator( $this->newPayPalUrlConfigWithDelayedPayment(), new FakeUrlAuthenticator(), $payment );

		$this->assertUrlValidForDelayedSubscriptions(
			$generator->generateUrl( $this->testRequestContext )
		);
	}

	private function assertUrlValidForDelayedSubscriptions( string $generatedUrl ): void {
		$this->assertCommonParamsSet( $generatedUrl );
		$this->assertSubscriptionRelatedParamsSet( $generatedUrl );
		$this->assertTrialPeriodRelatedParametersSet( $generatedUrl );
	}

	private function newPayPalUrlConfigWithDelayedPayment(): LegacyPayPalURLGeneratorConfig {
		$descriptionStub = $this->createStub( TranslatableDescription::class );
		$descriptionStub->method( 'getText' )->willReturn( 'Mentioning that awesome organization on the invoice' );
		return LegacyPayPalURLGeneratorConfig::newFromConfig(
			[
				'base-url' => self::BASE_URL,
				'locale' => self::LOCALE,
				'account-address' => self::ACCOUNT_ADDRESS,
				'notify-url' => self::NOTIFY_URL,
				'return-url' => self::RETURN_URL,
				'cancel-url' => self::CANCEL_URL,
				'delay-in-days' => 90
			],
			$descriptionStub
		);
	}

	private function assertCommonParamsSet( string $generatedUrl ): void {
		$this->assertStringContainsString( 'https://www.sandbox.paypal.com/cgi-bin/webscr', $generatedUrl );
		$this->assertStringContainsString( 'business=foerderpp%40wikimedia.de', $generatedUrl );
		$this->assertStringContainsString( 'currency_code=EUR', $generatedUrl );
		$this->assertStringContainsString( 'lc=de_DE', $generatedUrl );
		$this->assertStringContainsString( 'item_name=Mentioning+that+awesome+organization+on+the+invoice', $generatedUrl );
		$this->assertStringContainsString( 'item_number=1234', $generatedUrl );
		$this->assertStringContainsString( 'invoice=d1234', $generatedUrl );
		$this->assertStringContainsString( 'notify_url=https%3A%2F%2Fmy.donation.app%2Fhandler%2Fpaypal%2F', $generatedUrl );
		$this->assertStringContainsString( 'cancel_return=https%3A%2F%2Fmy.donation.app%2Fdonation%2Fcancel%2F', $generatedUrl );
		$this->assertStringContainsString(
			'return=https%3A%2F%2Fmy.donation.app%2Fdonation%2Fconfirm%2F%3Fid%3D1234%26testAccessToken%3DLET_ME_IN',
			$generatedUrl
		);
		$this->assertStringContainsString( 'custom=p-test-param-0', $generatedUrl );
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
