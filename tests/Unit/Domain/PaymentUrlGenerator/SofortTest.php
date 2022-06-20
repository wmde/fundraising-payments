<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort as SofortUrlGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\SofortConfig as SofortUrlConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\TranslatableDescription;
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
		$updateToken = 'UDtoken';
		$accessToken = 'XStoken';
		$locale = 'DE';
		$translatableDescription = $this->createMock( TranslatableDescription::class );

		$config = new SofortUrlConfig(
			$locale,
			'https://us.org/yes',
			'https://us.org/no',
			'https://us.org/callback',
			$translatableDescription
		);
		$client = new SofortSofortClientSpy( 'https://dn.ht/picklecat/' );
		$payment = SofortPayment::create(
			$internalItemId,
			$amount,
			PaymentInterval::OneTime,
			$this->createMock( PaymentReferenceCode::class ) );

		$urlGenerator = new SofortUrlGenerator( $config, $client, $payment );

		$requestContext = new RequestContext(
			$internalItemId,
			$externalItemId,
			$updateToken,
			$accessToken
		);
		$urlGenerator->generateUrl( $requestContext );

		$this->assertStringContainsString( "id=$internalItemId", $client->request->getSuccessUrl() );
		$this->assertStringContainsString( "id=$internalItemId", $client->request->getNotificationUrl() );
		$this->assertStringContainsString( "accessToken=$accessToken", $client->request->getSuccessUrl() );
		$this->assertStringContainsString( "updateToken=$updateToken", $client->request->getNotificationUrl() );
		$this->assertSame( $amount, $client->request->getAmount() );
		$this->assertSame( $locale, $client->request->getLocale() );
	}

	public function testSofortUrlGeneratorReturnsUrlFromClient(): void {
		$expectedUrl = 'https://dn.ht/picklecat/';
		$translatableDescriptionMock = $this->createMock( TranslatableDescription::class );
		$config = new SofortUrlConfig(
			'DE',
			'https://us.org/yes',
			'https://us.org/no',
			'https://us.org/callback',
			$translatableDescriptionMock
		);
		$client = new SofortSofortClientSpy( $expectedUrl );

		$payment = SofortPayment::create(
			23,
			Euro::newFromCents( 600 ),
			PaymentInterval::OneTime,
			$this->createMock( PaymentReferenceCode::class ) );

		$urlGenerator = new SofortUrlGenerator( $config, $client, $payment );

		$requestContext = new RequestContext(
			44,
			'wx529836',
			'up date token :)',
			'ax ess token :)' );
		$returnedUrl = $urlGenerator->generateUrl( $requestContext );

		$this->assertSame( $expectedUrl, $returnedUrl );
	}

	public function testWhenApiReturnsErrorAnExceptionWithApiErrorMessageIsThrown(): void {
		$translatableDescriptionStub = $this->createStub( TranslatableDescription::class );
		$config = new SofortUrlConfig(
			'DE',
			'https://irreleva.nt/y',
			'https://irreleva.nt/n',
			'https://irreleva.nt/api',
			$translatableDescriptionStub
		);
		$client = new ExceptionThrowingSofortSofortClient( 'boo boo' );
		$payment = SofortPayment::create(
			23,
			Euro::newFromCents( 600 ),
			PaymentInterval::OneTime,
			$this->createMock( PaymentReferenceCode::class ) );

		$urlGenerator = new SofortUrlGenerator( $config, $client, $payment );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not generate Sofort URL: boo boo' );

		$requestContext = new RequestContext(
			itemId: 23,
			updateToken: 'token_to_updateblabla',
			accessToken: 'token_to_accessblabla'

		);
		$urlGenerator->generateUrl( $requestContext );
	}
}
