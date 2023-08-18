<?php

declare( strict_types = 1 );

namespace Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\ExceptionThrowingSofortSofortClient;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SofortSofortClientSpy;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGenerator
 */
class SofortURLGeneratorTest extends TestCase {

	public function testSofortUrlGeneratorPassesValuesInRequestToClient(): void {
		$internalItemId = 44;
		$externalItemId = 'wx529836';
		$amount = Euro::newFromCents( 600 );
		$locale = 'DE';
		$translatableDescription = $this->createMock( TranslatableDescription::class );

		$config = new SofortURLGeneratorConfig(
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

		$urlGenerator = new SofortURLGenerator( $config, $client, new FakeUrlAuthenticator(), $payment );

		$requestContext = new RequestContext(
			$internalItemId,
			$externalItemId
		);
		$urlGenerator->generateUrl( $requestContext );

		$this->assertStringContainsString( "testAccessToken=LET_ME_IN", $client->request->getSuccessUrl() );
		$this->assertStringContainsString( "testAccessToken=LET_ME_IN", $client->request->getNotificationUrl() );
		$this->assertSame( $amount, $client->request->getAmount() );
		$this->assertSame( $locale, $client->request->getLocale() );
	}

	public function testSofortUrlGeneratorReturnsUrlFromClient(): void {
		$expectedUrl = 'https://dn.ht/picklecat/';
		$translatableDescriptionMock = $this->createMock( TranslatableDescription::class );
		$config = new SofortURLGeneratorConfig(
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

		$urlGenerator = new SofortURLGenerator( $config, $client, new FakeUrlAuthenticator(), $payment );

		$requestContext = new RequestContext(
			44,
			'wx529836',
			);
		$returnedUrl = $urlGenerator->generateUrl( $requestContext );

		$this->assertSame( $expectedUrl, $returnedUrl );
	}

	public function testWhenApiReturnsErrorAnExceptionWithApiErrorMessageIsThrown(): void {
		$translatableDescriptionStub = $this->createStub( TranslatableDescription::class );
		$config = new SofortURLGeneratorConfig(
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
			$this->createMock( PaymentReferenceCode::class )
		);

		$urlGenerator = new SofortURLGenerator( $config, $client, new FakeUrlAuthenticator(), $payment );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not generate Sofort URL: boo boo' );

		$requestContext = new RequestContext( itemId: 23 );
		$urlGenerator->generateUrl( $requestContext );
	}
}
