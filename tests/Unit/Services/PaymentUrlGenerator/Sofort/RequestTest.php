<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator\Sofort;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\Request;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\Request
 */
class RequestTest extends TestCase {

	public function testAccessors(): void {
		$request = new Request();

		$amount = Euro::newFromCents( 999 );
		$request->setAmount( $amount );
		$this->assertSame( $amount, $request->getAmount() );

		$request->setCurrencyCode( 'EUR' );
		$this->assertSame( 'EUR', $request->getCurrencyCode() );

		$request->setReasons( [ 'a', 'b' ] );
		$this->assertSame( [ 'a', 'b' ], $request->getReasons() );

		$request->setSuccessUrl( 'success' );
		$this->assertSame( 'success', $request->getSuccessUrl() );

		$request->setAbortUrl( 'abort' );
		$this->assertSame( 'abort', $request->getAbortUrl() );

		$request->setNotificationUrl( 'notify' );
		$this->assertSame( 'notify', $request->getNotificationUrl() );

		$request->setLocale( 'DE' );
		$this->assertSame( 'DE', $request->getLocale() );
	}
}
