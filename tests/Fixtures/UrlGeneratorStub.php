<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;

class UrlGeneratorStub implements PaymentProviderURLGenerator {
	public const URL = 'https://example.com/complete-payment';

	public function generateURL( RequestContext $requestContext ): string {
		return self::URL;
	}
}
