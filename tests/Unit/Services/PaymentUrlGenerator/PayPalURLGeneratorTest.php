<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalURLGenerator;

#[CoversClass( PayPalURLGenerator::class )]
class PayPalURLGeneratorTest extends TestCase {
	public function testUrlGeneratorReturnsUrl(): void {
		$url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-12345678901234567';
		$generator = new PayPalURLGenerator( $url );
		$this->assertSame( $url, $generator->generateURL( new DomainSpecificContext( 5 ) ) );
	}
}
