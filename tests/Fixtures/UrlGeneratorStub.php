<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;

class UrlGeneratorStub implements PaymentCompletionURLGenerator {
	public const URL = 'https://example.com/complete-payment';

	public function generateURL( DomainSpecificContext $requestContext ): string {
		return self::URL;
	}
}
