<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\DomainUrlTemplate;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificContext;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\DomainUrlTemplate
 */
class DomainUrlTemplateTest extends TestCase {
	public function testGivenLegacyURLItReplacesAllPlaceholders(): void {
		$context = new DomainSpecificContext(
			33,
			'accessToken123:updateToken456',
			'systemAccessToken'
		);

		$template = new DomainUrlTemplate( $context );

		$this->assertSame(
			'https://test-spenden.wikimedia.de/show-donation-confirmation?id=33&utoken=updateToken456&accessToken=accessToken123',
			$template->replacePlaceholders( 'https://test-spenden.wikimedia.de/show-donation-confirmation?id={{id}}&utoken={{updateToken}}&accessToken={{accessToken}}' )
		);
	}

	/**
	 * This test is an example for the confirmation URL when we implement https://phabricator.wikimedia.org/T344346
	 */
	public function testGivenModernURLItReplacesAllPlaceholders(): void {
		$context = new DomainSpecificContext(
			33,
			'userAccessToken789',
			'systemAccessToken'
		);

		$template = new DomainUrlTemplate( $context );

		$this->assertSame(
			'https://test-spenden.wikimedia.de/show-donation-confirmation/userAccessToken789',
			$template->replacePlaceholders( 'https://test-spenden.wikimedia.de/show-donation-confirmation/{{userAccessToken}}' )
		);
	}
}
