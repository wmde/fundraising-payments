<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalSubscription;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PayPalPaymentIdentifierRepository;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfig;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakePaymentReferenceCode;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapter
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfig
 */
class PayPalPaymentProviderAdapterTest extends TestCase {
	public function testGivenRecurringPaymentURLGeneratorIsReplacedWithPayPalUrlGeneratorFetchedFromAPI(): void {
		$adapter = new PayPalPaymentProviderAdapter(
			$this->givenAPIExpectingCreateSubscription(),
			$this->givenAdapterConfig(),
			$this->givenRepositoryStub()
		);
		$payment = new PayPalPayment( 6, Euro::newFromInt( 100 ), PaymentInterval::Quarterly );

		$urlGenerator = $adapter->modifyPaymentUrlGenerator( new IncompletePayPalURLGenerator( $payment ) );

		$this->assertSame( 'https://sandbox.paypal.com/confirm-subscription', $urlGenerator->generateURL( new RequestContext( 6 ) ) );
	}

	public function testGivenOneTimePaymentURLGeneratorIsReplacedWithPayPalUrlGeneratorFetchedFromAPI(): void {
		$adapter = new PayPalPaymentProviderAdapter(
			$this->givenAPIExpectingCreateOrder(),
			$this->givenAdapterConfig(),
			$this->givenRepositoryStub()
		);
		$payment = new PayPalPayment( 4, Euro::newFromInt( 27 ), PaymentInterval::OneTime );

		$urlGenerator = $adapter->modifyPaymentUrlGenerator( new IncompletePayPalURLGenerator( $payment ) );

		$this->assertSame( 'https://sandbox.paypal.com/confirm-order', $urlGenerator->generateURL( new RequestContext( 4 ) ) );
	}

	public function testAdapterOnlyAcceptsIncompletePayPalUrlGenerator(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'Expected instance of ' . IncompletePayPalURLGenerator::class . ', got ' . PayPalURLGenerator::class );
		$api = $this->createStub( PaypalAPI::class );
		$adapter = new PayPalPaymentProviderAdapter( $api, $this->givenAdapterConfig(), $this->givenRepositoryStub() );

		$adapter->modifyPaymentUrlGenerator( new PayPalURLGenerator( 'https://example.com' ) );
	}

	public function testGivenRecurringPaymentAdapterStoresPayPalSubscription(): void {
		$payment = new PayPalPayment( 6, Euro::newFromInt( 100 ), PaymentInterval::HalfYearly );
		$payPalSubscription = new PayPalSubscription( $payment, 'SUB-1234' );
		$repo = $this->createMock( PayPalPaymentIdentifierRepository::class );
		$repo->expects( $this->once() )->method( 'storePayPalIdentifier' )->with( $payPalSubscription );
		$adapter = new PayPalPaymentProviderAdapter(
			$this->givenAPIExpectingCreateSubscription(),
			$this->givenAdapterConfig(),
			$repo
		);

		$returnedPayment = $adapter->fetchAndStoreAdditionalData( $payment );

		$this->assertSame( $payment, $returnedPayment );
	}

	public function testGivenOneTimePaymentAdapterDoesNotStorePayPalOrder(): void {
		$payment = new PayPalPayment( 74, Euro::newFromInt( 470 ), PaymentInterval::OneTime );
		$repo = $this->createMock( PayPalPaymentIdentifierRepository::class );
		$repo->expects( $this->never() )->method( 'storePayPalIdentifier' );
		$adapter = new PayPalPaymentProviderAdapter(
			$this->givenAPIExpectingCreateOrder(),
			$this->givenAdapterConfig(),
			$repo
		);

		$returnedPayment = $adapter->fetchAndStoreAdditionalData( $payment );

		$this->assertSame( $payment, $returnedPayment );
	}

	public function testAdapterOnlyAcceptsPayPalPayments(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( PayPalPaymentProviderAdapter::class . ' only accepts ' . PayPalPayment::class );
		$api = $this->createStub( PaypalAPI::class );
		$adapter = new PayPalPaymentProviderAdapter( $api, $this->givenAdapterConfig(), $this->givenRepositoryStub() );

		$adapter->fetchAndStoreAdditionalData(
			SofortPayment::create( 5, Euro::newFromCents( 4775 ), PaymentInterval::OneTime, new FakePaymentReferenceCode() )
		);
	}

	public function testAdapterCallsAPIOnlyOnce(): void {
		$repo = $this->createStub( PayPalPaymentIdentifierRepository::class );
		$adapter = new PayPalPaymentProviderAdapter(
			$this->givenAPIExpectingCreateSubscription(),
			$this->givenAdapterConfig(),
			$repo
		);
		$payment = new PayPalPayment( 6, Euro::newFromInt( 100 ), PaymentInterval::Quarterly );

		$adapter->modifyPaymentUrlGenerator( new IncompletePayPalURLGenerator( $payment ) );
		$adapter->fetchAndStoreAdditionalData( $payment );
		$adapter->modifyPaymentUrlGenerator( new IncompletePayPalURLGenerator( $payment ) );
	}

	private function givenAdapterConfig(): PayPalPaymentProviderAdapterConfig {
		return new PayPalPaymentProviderAdapterConfig(
			'your donation',
			'https://example.com/confirmed?id={{id}}&utoken={{updateToken}}&accessToken={{accessToken}}',
			'https://example.com/new?id={{id}}&utoken={{updateToken}}&accessToken={{accessToken}}',
			[
				PaymentInterval::Monthly->name => new SubscriptionPlan( 'Monthly donation', 'Donation-1', PaymentInterval::Monthly, 'P-123' ),
				PaymentInterval::Quarterly->name => new SubscriptionPlan( 'Quarterly donation', 'Donation-1', PaymentInterval::Quarterly, 'P-456' ),
				PaymentInterval::HalfYearly->name => new SubscriptionPlan( 'Half-yearly donation', 'Donation-1', PaymentInterval::HalfYearly, 'P-789' ),
				PaymentInterval::Yearly->name => new SubscriptionPlan( 'Yearly donation', 'Donation-1', PaymentInterval::Yearly, 'P-ABC' )
			]
		);
	}

	private function givenRepositoryStub(): PayPalPaymentIdentifierRepository {
		$stub = $this->createMock( PayPalPaymentIdentifierRepository::class );
		$stub->expects( $this->never() )->method( 'storePayPalIdentifier' );
		return $stub;
	}

	private function givenAPIExpectingCreateOrder(): PaypalAPI {
		$api = $this->createStub( PayPalAPI::class );
		$api->method( 'createOrder' )->willReturn(
			new Order( 'SOME-ORDER-ID', 'https://sandbox.paypal.com/confirm-order' )
		);
		return $api;
	}

	private function givenAPIExpectingCreateSubscription(): PaypalAPI {
		$api = $this->createMock( PayPalAPI::class );
		$api->expects( $this->once() )
			->method( 'createSubscription' )
			->willReturn(
				new Subscription( 'SUB-1234', new \DateTimeImmutable(), 'https://sandbox.paypal.com/confirm-subscription' )
			);
		return $api;
	}
}
