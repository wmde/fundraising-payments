<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator
 */
class IncompletePayPalURLGeneratorTest extends TestCase {
	public function testURLGeneratorThrowsOnGenerateURL(): void {
		$generator = new IncompletePayPalURLGenerator( new PayPalPayment( 5, Euro::newFromCents( 123 ), PaymentInterval::Monthly ) );
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessageMatches( '/instance should be replaced/' );
		$generator->generateURL( new DomainSpecificContext( 5 ) );
	}
}
