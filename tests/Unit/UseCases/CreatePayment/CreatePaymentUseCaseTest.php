<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\UrlGeneratorFactory;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\StaticPaymentIDRepository;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationException
 */
class CreatePaymentUseCaseTest extends TestCase {

	private CreatePaymentUseCaseBuilder $useCaseBuilder;

	protected function setUp(): void {
		parent::setUp();
		$this->useCaseBuilder = new CreatePaymentUseCaseBuilder();
	}

	public function testCreateCreditCardPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new StaticPaymentIDRepository() )
			->withPaymentRepositorySpy()
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( StaticPaymentIDRepository::STATIC_ID, $result->paymentId );
		$this->assertPaymentWasStored(
			new CreditCardPayment(
				StaticPaymentIDRepository::STATIC_ID,
				Euro::newFromCents( 100 ),
				PaymentInterval::OneTime
			)
		);
	}

	public function testCreatePayPalPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new StaticPaymentIDRepository() )
			->withPaymentRepositorySpy()
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'PPL'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( StaticPaymentIDRepository::STATIC_ID, $result->paymentId );
		$this->assertPaymentWasStored(
			new PayPalPayment(
				StaticPaymentIDRepository::STATIC_ID,
				Euro::newFromCents( 100 ),
				PaymentInterval::OneTime
			)
		);
	}

	public function testCreateSofortPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new StaticPaymentIDRepository() )
			->withPaymentRepositorySpy()
			->withPaymentReferenceGenerator( $this->makePaymentReferenceGenerator() )
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'SUB',
			transferCodePrefix: 'XW'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( StaticPaymentIDRepository::STATIC_ID, $result->paymentId );
		$this->assertPaymentWasStored( SofortPayment::create(
			StaticPaymentIDRepository::STATIC_ID,
			Euro::newFromCents( 100 ),
			PaymentInterval::OneTime,
			PaymentReferenceCode::newFromString( 'XW-DAR-E99-X' )
		) );
	}

	public function testCreateBankTransferPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new StaticPaymentIDRepository() )
			->withPaymentRepositorySpy()
			->withPaymentReferenceGenerator( $this->makePaymentReferenceGenerator() )
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'UEB',
			transferCodePrefix: 'XW'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( StaticPaymentIDRepository::STATIC_ID, $result->paymentId );
		$this->assertPaymentWasStored( BankTransferPayment::create(
			StaticPaymentIDRepository::STATIC_ID,
			Euro::newFromCents( 400 ),
			PaymentInterval::Quarterly,
			new PaymentReferenceCode( 'XW', 'DARE99', 'X' )
		) );
	}

	public function testCreateSofortPaymentFailsOnUnsupportedInterval(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: PaymentInterval::Monthly->value,
			paymentType: 'SUB',
			transferCodePrefix: 'TestPrefix'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidIntervalFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 1000,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidAmountFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: -500,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidPaymentTypeFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 500,
			interval: 0,
			paymentType: 'TRA$HCOIN',
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreateDirectDebitPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new StaticPaymentIDRepository() )
			->withPaymentRepositorySpy()
			->withSucceedingIbanValidationUseCase()
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'BEZ',
			iban: DirectDebitBankData::IBAN,
			bic: DirectDebitBankData::BIC
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( StaticPaymentIDRepository::STATIC_ID, $result->paymentId );
		$this->assertPaymentWasStored( DirectDebitPayment::create(
			StaticPaymentIDRepository::STATIC_ID,
			Euro::newFromCents( 400 ),
			PaymentInterval::Quarterly,
			new Iban( DirectDebitBankData::IBAN ),
			DirectDebitBankData::BIC
		) );
	}

	public function testCreateDirectDebitPaymentWithInvalidIbanFails(): void {
		$useCase = $this->useCaseBuilder
			->withFailingIbanValidationUseCase()
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'BEZ',
			iban: DirectDebitBankData::IBAN,
			bic: DirectDebitBankData::BIC
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertEquals( "An invalid Iban was provided", $result->errorMessage );
	}

	public function testPaymentResponseContainsURLGeneratorFromFactory(): void {
		$urlGenerator = $this->createStub( PaymentProviderURLGenerator::class );
		$urlGeneratorFactory = $this->createStub( UrlGeneratorFactory::class );
		$urlGeneratorFactory->method( 'createURLGenerator' )->willReturn( $urlGenerator );
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new StaticPaymentIDRepository() )
			->withPaymentRepositorySpy()
			->withUrlGeneratorFactory( $urlGeneratorFactory )
			->build();

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'PPL',
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( $urlGenerator, $result->paymentProviderURLGenerator );
	}

	private function makePaymentReferenceGenerator(): PaymentReferenceCodeGenerator {
		$referenceCodeGenerator = $this->createMock( PaymentReferenceCodeGenerator::class );
		$referenceCodeGenerator->expects( $this->once() )
			->method( 'newPaymentReference' )
			->with( 'XW' )
			->willReturn( new PaymentReferenceCode( 'XW', 'DARE99', 'X' ) );
		return $referenceCodeGenerator;
	}

	private function assertPaymentWasStored( Payment $expectedPayment ): void {
		$actualPayment = $this->useCaseBuilder->getPaymentRepository()->getPaymentById( StaticPaymentIDRepository::STATIC_ID );
		$this->assertEquals( $expectedPayment, $actualPayment );
	}
}
