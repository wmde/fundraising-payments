<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort as SofortUrlGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\SofortConfig as SofortUrlConfig;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\ExceptionThrowingSofortSofortClient;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SofortSofortClientSpy;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort
 */
class SofortTest extends TestCase {

	public function testSofortUrlGeneratorPassesValuesInRequestToClient(): void {
		$internalItemId = 44;
		$externalItemId = 'wx529836';
		$amount = Euro::newFromCents( 600 );
		$updateToken = 'makesandwich';
		$accessToken = 'letmein';
		$locale = 'DE';

		$config = new SofortUrlConfig(
			'Donation',
			$locale,
			'https://us.org/yes',
			'https://us.org/no',
			'https://us.org/callback'
		);
		$client = new SofortSofortClientSpy( 'https://dn.ht/picklecat/' );
		$urlGenerator = new SofortUrlGenerator( $config, $client );

		$urlGenerator->generateUrl( $internalItemId, $externalItemId, $amount, $updateToken, $accessToken );

		$this->assertStringContainsString( "id=$internalItemId", $client->request->getSuccessUrl() );
		$this->assertStringContainsString( "id=$internalItemId", $client->request->getNotificationUrl() );
		$this->assertStringContainsString( "accessToken=$accessToken", $client->request->getSuccessUrl() );
		$this->assertStringContainsString( "updateToken=$updateToken", $client->request->getNotificationUrl() );
		$this->assertSame( $amount, $client->request->getAmount() );
		$this->assertSame( $locale, $client->request->getLocale() );
	}

	public function testSofortUrlGeneratorReturnsUrlFromClient(): void {
		$expectedUrl = 'https://dn.ht/picklecat/';
		$config = new SofortUrlConfig(
			'Donation',
			'DE',
			'https://us.org/yes',
			'https://us.org/no',
			'https://us.org/callback'
		);
		$client = new SofortSofortClientSpy( $expectedUrl );
		$urlGenerator = new SofortUrlGenerator( $config, $client );

		$returnedUrl = $urlGenerator->generateUrl( 44, 'wx529836', Euro::newFromCents( 600 ), 'makesandwich', 'letmein' );

		$this->assertSame( $expectedUrl, $returnedUrl );
	}

	public function testWhenApiReturnsErrorAnExceptionWithApiErrorMessageIsThrown(): void {
		$config = new SofortUrlConfig(
			'Your purchase',
			'DE',
			'https://irreleva.nt/y',
			'https://irreleva.nt/n',
			'https://irreleva.nt/api'
		);
		$client = new ExceptionThrowingSofortSofortClient( 'boo boo' );
		$urlGenerator = new SofortUrlGenerator( $config, $client );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not generate Sofort URL: boo boo' );

		$urlGenerator->generateUrl( 23, 'dq529837', Euro::newFromCents( 300 ), 'makesandwich', 'letmein' );
	}
}
