<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\NullGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\NullGenerator
 */
class NullGeneratorTest extends TestCase {

	public function testURLGenerationReturnsEmptyURL(): void {
		$contextMock = $this->createMock( RequestContext::class );

		$nullGenerator = new NullGenerator();

		self::assertSame( '', $nullGenerator->generateURL( $contextMock ) );
	}

}