<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PaymentUrlGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;

#[CoversClass( CreditCardURLGenerator::class )]
#[CoversClass( DomainSpecificContext::class )]
class CreditCardURLGeneratorTest extends TestCase {

	#[DataProvider( 'donationProvider' )]
	public function testUrlGeneration(
		string $expected,
		string $firstName,
		string $lastName,
		string $description,
		int $donationId,
		string $accessToken,
		string $updateToken,
		string $paymentReferenceCode,
		Euro $amount,
		PaymentInterval $interval
	): void {
		$translatableDescriptionMock = $this->createMock( TranslatableDescription::class );
		$translatableDescriptionMock->method( 'getText' )->willReturn( $description );

		$urlGenerator = new CreditCardURLGenerator(
			CreditCardURLGeneratorConfig::newFromConfig(
				[
					'base-url' => 'https://credit-card.micropayment.de/creditcard/event/index.php?',
					'project-id' => 'wikimedia',
					'locale' => 'de',
					'background-color' => 'CCE7CD',
					'logo' => 'wikimedia_black',
					'theme' => 'wikimedia',
					'testmode' => false
				],
				$translatableDescriptionMock
			),
			new FakeUrlAuthenticator(),
			new CreditCardPayment( 42, $amount, $interval )
		);

		$requestContext = new DomainSpecificContext(
			itemId: $donationId,
			firstName: $firstName,
			lastName: $lastName,
		);
		$this->assertSame(
			$expected,
			$urlGenerator->generateUrl( $requestContext )
		);
	}

	public function testWhenTestModeIsEnabled_urlPassesProperParameter(): void {
		$translatableDescriptionMock = $this->createStub( TranslatableDescription::class );
		$translatableDescriptionMock->method( 'getText' )->willReturn( 'Ich spende einmalig' );
		$urlGenerator = new CreditCardURLGenerator(
			CreditCardURLGeneratorConfig::newFromConfig(
				[
					'base-url' => 'https://credit-card.micropayment.de/creditcard/event/index.php?',
					'project-id' => 'wikimedia',
					'locale' => 'de',
					'background-color' => 'CCE7CD',
					'logo' => 'wikimedia_black',
					'theme' => 'wikimedia',
					'testmode' => true
				],
				$translatableDescriptionMock
			),
			new FakeUrlAuthenticator(),
			new CreditCardPayment( 32, Euro::newFromCents( 100 ), PaymentInterval::OneTime )
		);

		$requestContext = new DomainSpecificContext(
			itemId: 1234567,
			firstName: "Kai",
			lastName: "Nissen",
		);
		$this->assertSame(
			'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
			'paytext=Ich+spende+einmalig&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&gfx=wikimedia_black&' .
			'amount=100&theme=wikimedia&producttype=fee&lang=de&token=p-test-param-0&utoken=p-test-param-1&testmode=1',
			$urlGenerator->generateUrl( $requestContext )
		);
	}

	/**
	 * @return array<mixed[]>
	 */
	public static function donationProvider(): array {
		return [
			[
				'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
				'paytext=Ich+spende+einmalig&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&gfx=wikimedia_black&' .
				'amount=500&theme=wikimedia&producttype=fee&lang=de&token=p-test-param-0&utoken=p-test-param-1',
				'Kai',
				'Nissen',
				'Ich spende einmalig',
				1234567,
				'my_access_token',
				'my_update_token',
				'iamAReferenceCodeOfThisPayment',
				Euro::newFromFloat( 5.00 ),
				PaymentInterval::OneTime
			],
			[
				'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
				'paytext=Ich+spende+monatlich&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&gfx=wikimedia_black&' .
				'amount=123&theme=wikimedia&producttype=fee&lang=de&token=p-test-param-0&utoken=p-test-param-1',
				'Kai',
				'Nissen',
				'Ich spende monatlich',
				1234567,
				'my_access_token',
				'my_update_token',
				'iamAReferenceCodeOfThisPayment',
				Euro::newFromFloat( 1.23 ),
				PaymentInterval::Monthly
			],
			[
				'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
				'paytext=Ich+spende+halbj%C3%A4hrlich&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&' .
				'gfx=wikimedia_black&amount=1250&theme=wikimedia&producttype=fee&lang=de&token=p-test-param-0&utoken=p-test-param-1',
				'Kai',
				'Nissen',
				'Ich spende halbjährlich',
				1234567,
				'my_access_token',
				'my_update_token',
				'iamAReferenceCodeOfThisPayment',
				Euro::newFromFloat( 12.5 ),
				PaymentInterval::HalfYearly
			],
		];
	}

}
