<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use http\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PaypalData
 *
 * @licence GNU GPL v2+
 */
class PaypalDataTest extends TestCase {

	public function testCanGetAndSetPaymentDate(): void {
		$now = new \DateTimeImmutable( '2019-12-24 20:15:00' );
		$pplData = new PayPalData();
		$pplData->setPaymentTime( $now );
		$this->assertEquals( $now, $pplData->getPaymentTime() );
	}

	public function testGivenInvalidTimeFormatThroughDeprecatedSetterItThrowsAnException(): void {
		$pplData = new PayPalData();
		$this->expectException( \InvalidArgumentException::class );
		$pplData->setPaymentTimestamp( '2019-12-24 20:15:00' );
	}
}


