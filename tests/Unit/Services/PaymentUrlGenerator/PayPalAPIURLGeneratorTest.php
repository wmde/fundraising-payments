<?php
declare( strict_types=1 );

namespace Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalAPIURLGenerator as PayPalAPIUrlGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalAPIURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalAPIURLGenerator
 */
class PayPalAPIURLGeneratorTest extends TestCase {
	public function testGivenOneTimePaymentReturnsUrlFromOrder(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 800 ), PaymentInterval::OneTime );
		$payPalApi = $this->createMock( PaypalAPI::class );
		$orderParameters = new OrderParameters(
			"someInvoiceID",
			"222",
			"your donation",
			Euro::newFromCents( 800 ),
			"https://example.com/confirmed?id=222&utoken=someUpdateToken&token=some-access-token",
			"https://example.com/new?id=222&utoken=someUpdateToken&token=some-access-token"
		);
		$payPalApi
			->expects( $this->once() )
			->method( 'createOrder' )
			->with( $orderParameters )
			->willReturn( new Order( 'order123', 'https://example.com/paypal' ) );
		$generator = new PayPalAPIUrlGenerator(
			$payPalApi,
			$this->givenApiConfig(),
			$payment
		);

		$generatedURL = $generator->generateURL( new RequestContext(
			222,
			"someInvoiceID",
			"someUpdateToken",
			"some-access-token",
			"Benjamin",
			"Blümchen"
		) );

		$this->assertSame( 'https://example.com/paypal', $generatedURL );
	}

	public function testGivenRecurringPaymentReturnsUrlFromSubscription(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 980 ), PaymentInterval::HalfYearly );
		$payPalApi = $this->createMock( PaypalAPI::class );
		$subscriptionPlan = new SubscriptionPlan( 'Half-yearly donation', 'Donation-1', PaymentInterval::HalfYearly, 'P-789' );
		$subscriptionParameters = new SubscriptionParameters(
			$subscriptionPlan,
			Euro::newFromCents( 980 ),
			"https://example.com/confirmed?id=222&utoken=someUpdateToken&token=some-access-token",
			"https://example.com/new?id=222&utoken=someUpdateToken&token=some-access-token"
		);
		$payPalApi
			->expects( $this->once() )
			->method( 'createSubscription' )
			->with( $subscriptionParameters )
			->willReturn( new Subscription( 'P-12345', new \DateTimeImmutable(), 'https://example.com/paypal' ) );
		$generator = new PayPalAPIUrlGenerator(
			$payPalApi,
			$this->givenApiConfig(),
			$payment
		);

		$generatedURL = $generator->generateURL( new RequestContext(
			222,
			"someInvoiceID",
			"someUpdateToken",
			"some-access-token",
			"Benjamin",
			"Blümchen"
		) );

		$this->assertSame( 'https://example.com/paypal', $generatedURL );
	}

	private function givenApiConfig(): PayPalAPIURLGeneratorConfig {
		return new PayPalAPIURLGeneratorConfig(
			'your donation',
			'https://example.com/confirmed?id={{id}}&utoken={{updateToken}}&token={{accessToken}}',
			'https://example.com/new?id={{id}}&utoken={{updateToken}}&token={{accessToken}}',
			[
				PaymentInterval::Monthly->name => new SubscriptionPlan( 'Monthly donation', 'Donation-1', PaymentInterval::Monthly, 'P-123' ),
				PaymentInterval::Quarterly->name => new SubscriptionPlan( 'Quarterly donation', 'Donation-1', PaymentInterval::Quarterly, 'P-456' ),
				PaymentInterval::HalfYearly->name => new SubscriptionPlan( 'Half-yearly donation', 'Donation-1', PaymentInterval::HalfYearly, 'P-789' ),
				PaymentInterval::Yearly->name => new SubscriptionPlan( 'Yearly donation', 'Donation-1', PaymentInterval::Yearly, 'P-ABC' )
			]
		);
	}

}
