<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificContext;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificContext
 */
class DomainSpecificContextTest extends TestCase {
	public function testItCreatesRequestContextForUrlGenerator(): void {
		$context = new DomainSpecificContext(
			7,
			null,
			'M-7',
			'Kif',
			'Kroker',
		);

		$requestContext = $context->getRequestContextForUrlGenerator();

		$expectedRequestContext = new RequestContext(
			7,
			'M-7',
			'Kif',
			'Kroker'
		);
		$this->assertEquals( $expectedRequestContext, $requestContext );
	}
}
