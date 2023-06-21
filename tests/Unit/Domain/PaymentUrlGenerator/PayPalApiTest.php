<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalAPI as PayPalAPIUrlGenerator;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalAPIConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalAPI
 */
class PayPalApiTest extends TestCase {
	public function testGivenOneTimePaymentCreatesAnOrder(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 800 ), PaymentInterval::OneTime );
		$payPalApi = $this->createMock( PaypalAPI::class );

		$orderParameters = new OrderParameters(
			"someInvoiceID",
			"222",
			"your donation",
			Euro::newFromCents( 800 ),
			"https://example.com/confirmed?id=222&utoken=someUpdateToken&token=some-access-token",
			"https://example.com/new?id=222&utoken=someUpdateToken&atoken=some-access-token"
		);
		$payPalApi
			->expects( $this->once() )
			->method('createOrder' )
			->with( $orderParameters )
			->willReturn( new Order( 'order123', 'https://example.com/paypal') );
		$generator = new PayPalAPIUrlGenerator(
			$payPalApi,
			new PayPalAPIConfig('your donation', 'https://example.com/confirmed?id={{id}}&utoken={{updateToken}}&token={{accessToken}}', ''),
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

		$this->assertSame( 'https://example.com/paypal' , $generatedURL );
	}
}
