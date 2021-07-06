<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCard;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCardConfig;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCard
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCardTest extends \PHPUnit\Framework\TestCase {

	/** @dataProvider donationProvider */
	public function testUrlGeneration( string $expected, string $firstName, string $lastName, string $payText,
		int $donationId, string $accessToken, string $updateToken, Euro $amount ): void {
		$urlGenerator = new CreditCard(
			CreditCardConfig::newFromConfig(
				[
					'base-url' => 'https://credit-card.micropayment.de/creditcard/event/index.php?',
					'project-id' => 'wikimedia',
					'locale' => 'de',
					'background-color' => 'CCE7CD',
					'logo' => 'wikimedia_black',
					'theme' => 'wikimedia',
					'testmode' => false
				]
			)
		);
		$this->assertSame(
			$expected,
			$urlGenerator->generateUrl(
				$firstName,
				$lastName,
				$payText,
				$donationId,
				$accessToken,
				$updateToken,
				$amount
			)
		);
	}

	public function testWhenTestModeIsEnabled_urlPassesProperParameter(): void {
		$urlGenerator = new CreditCard(
			CreditCardConfig::newFromConfig(
				[
					'base-url' => 'https://credit-card.micropayment.de/creditcard/event/index.php?',
					'project-id' => 'wikimedia',
					'locale' => 'de',
					'background-color' => 'CCE7CD',
					'logo' => 'wikimedia_black',
					'theme' => 'wikimedia',
					'testmode' => true
				]
			)
		);
		$this->assertSame(
			'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
			'paytext=Ich+spende+einmalig&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&gfx=wikimedia_black&' .
			'token=my_access_token&utoken=my_update_token&amount=500&theme=wikimedia&producttype=fee&lang=de&testmode=1',
			$urlGenerator->generateUrl(
				'Kai',
				'Nissen',
				'Ich spende einmalig',
				1234567,
				'my_access_token',
				'my_update_token',
				Euro::newFromFloat( 5.00 )
			)
		);
	}

	public function donationProvider(): array {
		return [
			[
				'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
				'paytext=Ich+spende+einmalig&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&gfx=wikimedia_black&' .
				'token=my_access_token&utoken=my_update_token&amount=500&theme=wikimedia&producttype=fee&lang=de',
				'Kai',
				'Nissen',
				'Ich spende einmalig',
				1234567,
				'my_access_token',
				'my_update_token',
				Euro::newFromFloat( 5.00 )
			],
			[
				'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
				'paytext=Ich+spende+monatlich&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&gfx=wikimedia_black&' .
				'token=my_access_token&utoken=my_update_token&amount=123&theme=wikimedia&producttype=fee&lang=de',
				'Kai',
				'Nissen',
				'Ich spende monatlich',
				1234567,
				'my_access_token',
				'my_update_token',
				Euro::newFromFloat( 1.23 )
			],
			[
				'https://credit-card.micropayment.de/creditcard/event/index.php?project=wikimedia&bgcolor=CCE7CD&' .
				'paytext=Ich+spende+halbj%C3%A4hrlich&mp_user_firstname=Kai&mp_user_surname=Nissen&sid=1234567&' .
				'gfx=wikimedia_black&token=my_access_token&utoken=my_update_token&amount=1250&theme=wikimedia&' .
				'producttype=fee&lang=de',
				'Kai',
				'Nissen',
				'Ich spende halbjährlich',
				1234567,
				'my_access_token',
				'my_update_token',
				Euro::newFromFloat( 12.5 )
			],
		];
	}

}
