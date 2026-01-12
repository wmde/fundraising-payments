<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\TranslatableDescription;
use WMDE\Fundraising\PaymentContext\Tests\Data\DomainSpecificContextForTesting;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DefaultPaymentProviderAdapter;

#[CoversClass( DefaultPaymentProviderAdapter::class )]
class DefaultPaymentProviderAdapterTest extends TestCase {
	public function testItReturnsInputsUnchanged(): void {
		$adapter = new DefaultPaymentProviderAdapter();
		$payment = new CreditCardPayment( 4, Euro::newFromInt( 100 ), PaymentInterval::HalfYearly );
		$urlGenerator = $this->givenCreditCardURLGenerator( $payment );
		$context = DomainSpecificContextForTesting::create();

		$this->assertSame( $payment, $adapter->fetchAndStoreAdditionalData( $payment, $context ) );
		$this->assertSame( $urlGenerator, $adapter->modifyPaymentUrlGenerator( $urlGenerator, $context ) );
	}

	private function givenCreditCardURLGenerator( CreditCardPayment $payment ): CreditCardURLGenerator {
		return new CreditCardURLGenerator(
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
				$this->createStub( TranslatableDescription::class )
			),
			new FakeUrlAuthenticator(),
			$payment );
	}
}
