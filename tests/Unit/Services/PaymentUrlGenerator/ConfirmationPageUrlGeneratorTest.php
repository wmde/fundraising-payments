<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\ConfirmationPageUrlGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\ConfirmationPageUrlGenerator
 */
class ConfirmationPageUrlGeneratorTest extends TestCase {
	public function testItAppendsAuthenticationTokensToConfirmationUrl(): void {
		$generator = new ConfirmationPageUrlGenerator(
			'https://spenden.wikimedia.de/confirmation',
			new FakeUrlAuthenticator()
		);

		$this->assertSame(
			'https://spenden.wikimedia.de/confirmation?testAccessToken=LET_ME_IN',
			$generator->generateURL( new DomainSpecificContext( 1, ) )
		);
	}

}
