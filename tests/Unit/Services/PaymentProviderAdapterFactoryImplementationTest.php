<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PayPalPaymentIdentifierRepository;
use WMDE\Fundraising\PaymentContext\Services\PaymentProviderAdapterFactoryImplementation;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfig;
use WMDE\Fundraising\PaymentContext\Tests\Data\TestIban;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakePaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DefaultPaymentProviderAdapter;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentProviderAdapterFactoryImplementation
 */
class PaymentProviderAdapterFactoryImplementationTest extends TestCase {
	/**
	 * @dataProvider allPaymentsExceptPayPal
	 */
	public function testItCreatesDefaultAdapterForAllPaymentTypesExceptPayPal( Payment $payment ): void {
		$factory = new PaymentProviderAdapterFactoryImplementation(
			$this->createStub( PaypalAPI::class ),
			$this->createStub( PayPalPaymentProviderAdapterConfig::class ),
			$this->createStub( PayPalPaymentIdentifierRepository::class ),
		);
		$adapter = $factory->createProvider( $payment, new FakeUrlAuthenticator() );
		$this->assertInstanceOf( DefaultPaymentProviderAdapter::class, $adapter );
	}

	public function testItCreatedPayPalAdapterForPayPalPayments(): void {
		$factory = new PaymentProviderAdapterFactoryImplementation(
			$this->createStub( PaypalAPI::class ),
			$this->createStub( PayPalPaymentProviderAdapterConfig::class ),
			$this->createStub( PayPalPaymentIdentifierRepository::class ),
		);
		$payment = new PayPalPayment( 5, Euro::newFromCents( 10000 ), PaymentInterval::Yearly );
		$adapter = $factory->createProvider( $payment, new FakeUrlAuthenticator() );
		$this->assertInstanceOf( PayPalPaymentProviderAdapter::class, $adapter );
	}

	/**
	 * @return iterable<array{Payment}>
	 */
	public static function allPaymentsExceptPayPal(): iterable {
		yield 'bank transfer payment' => [ BankTransferPayment::create( 1, Euro::newFromCents( 100 ), PaymentInterval::Monthly, new FakePaymentReferenceCode() ) ];
		yield 'credit card payment' => [ new CreditCardPayment( 2, Euro::newFromCents( 100 ), PaymentInterval::Yearly ) ];
		yield 'direct debit payment' => [ DirectDebitPayment::create( 3, Euro::newFromCents( 100 ), PaymentInterval::HalfYearly, new TestIban(), '' ) ];
		yield 'sofort payment' => [ SofortPayment::create( 4, Euro::newFromCents( 100 ), PaymentInterval::OneTime, new FakePaymentReferenceCode() ) ];
	}
}
