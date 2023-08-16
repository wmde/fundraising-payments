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
			'user-123',
			'system-456',
			null,
			'M-7',
			'Kif',
			'Kroker',
		);

		$requestContext = $context->getRequestContextForUrlGenerator();

		$expectedRequestContext = new RequestContext(
			7,
			'M-7',
			'system-456',
			'user-123',
			'Kif',
			'Kroker'
		);
		$this->assertEquals( $expectedRequestContext, $requestContext );
	}

	public function testGivenUserTokenWithColonItCreatesRequestContextWithUserTokenOnly(): void {
		$context = new DomainSpecificContext(
			7,
			'access-123:update-456',
			'I_should_be_ignored',
			null,
			'M-7',
			'Kif',
			'Kroker',
		);

		$requestContext = $context->getRequestContextForUrlGenerator();

		$this->assertSame( 'update-456', $requestContext->updateToken );
		$this->assertSame( 'access-123', $requestContext->accessToken );
	}

	/**
	 * @param string $userAccessToken
	 * @param array{accessToken:string,updateToken:string} $expectedTokens
	 *
	 * @dataProvider provideUserAccessTokens
	 */
	public function testItCanCreateLegacyTokensFromUserAccessToken( string $userAccessToken, array $expectedTokens ): void {
		$context = new DomainSpecificContext(
			7,
			$userAccessToken,
			'I_should_be_ignored',
		);

		$tokens = $context->getLegacyTokens();

		$this->assertSame( $expectedTokens['accessToken'], $tokens['accessToken'] );
		$this->assertSame( $expectedTokens['updateToken'], $tokens['updateToken'] );
	}

	/**
	 * @return iterable<array{string,array{accessToken:string,updateToken:string}}>
	 */
	public static function provideUserAccessTokens(): iterable {
		yield 'legacy token' => [ 'access-123:update-456', [ 'accessToken' => 'access-123', 'updateToken' => 'update-456' ] ];
		yield 'modern token' => [ 'NO_COLON_HERE_78944', [ 'accessToken' => 'NO_COLON_HERE_78944',  'updateToken' => '' ] ];
	}
}
