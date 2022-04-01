<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\RequestContext;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\NullGenerator
 */
class NullGeneratorTest extends TestCase {

	public function testURLGenerationReturnsEmptyURL(): void {
		$contextMock = $this->createMock( RequestContext::class );

		$nullGenerator = new NullGenerator();

		self::assertEmpty( $nullGenerator->generateURL( $contextMock ) );
	}

}
