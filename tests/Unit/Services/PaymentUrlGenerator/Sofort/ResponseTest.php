<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator\Sofort;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\Response;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\Response
 */
class ResponseTest extends TestCase {

	public function testAccessors(): void {
		$response = new Response();

		$this->assertSame( '', $response->getPaymentUrl() );
		$this->assertSame( '', $response->getTransactionId() );

		$response->setPaymentUrl( 'foo.com' );
		$response->setTransactionId( '12345' );

		$this->assertSame( 'foo.com', $response->getPaymentUrl() );
		$this->assertSame( '12345', $response->getTransactionId() );
	}
}
