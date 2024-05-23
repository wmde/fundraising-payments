<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
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
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\UrlGeneratorFactory;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\Tests\Data\DomainSpecificContextForTesting;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FailingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SequentialPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\UrlGeneratorStub;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationException
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse
 * @covers \WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext
 */
class CreatePaymentUseCaseTest extends TestCase {

	private const PAYMENT_ID = 1;

	private CreatePaymentUseCaseBuilder $useCaseBuilder;

	protected function setUp(): void {
		parent::setUp();
		$this->useCaseBuilder = new CreatePaymentUseCaseBuilder();
	}

	public function testCreateCreditCardPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
		$this->assertFalse( $result->paymentComplete );
		$this->assertPaymentWasStored(
			new CreditCardPayment(
				self::PAYMENT_ID,
				Euro::newFromCents( 100 ),
				PaymentInterval::OneTime
			)
		);
	}

	public function testCreatePayPalPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'PPL'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
		$this->assertFalse( $result->paymentComplete );
		$this->assertPaymentWasStored(
			new PayPalPayment(
				self::PAYMENT_ID,
				Euro::newFromCents( 100 ),
				PaymentInterval::OneTime
			)
		);
	}

	public function testCreateSofortPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->withPaymentReferenceGenerator( $this->makePaymentReferenceGenerator() )
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'SUB',
			transferCodePrefix: 'XW'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
		$this->assertFalse( $result->paymentComplete );
		$this->assertPaymentWasStored( SofortPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 100 ),
			PaymentInterval::OneTime,
			PaymentReferenceCode::newFromString( 'XW-DAR-E99-X' )
		) );
	}

	public function testCreateBankTransferPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->withPaymentReferenceGenerator( $this->makePaymentReferenceGenerator() )
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'UEB',
			transferCodePrefix: 'XW'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
		$this->assertTrue( $result->paymentComplete );
		$this->assertPaymentWasStored( BankTransferPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 400 ),
			PaymentInterval::Quarterly,
			new PaymentReferenceCode( 'XW', 'DARE99', 'X' )
		) );
	}

	public function testCreateDirectDebitPayment(): void {
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->withSucceedingIbanValidationUseCase()
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'BEZ',
			iban: DirectDebitBankData::IBAN,
			bic: DirectDebitBankData::BIC
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
		$this->assertTrue( $result->paymentComplete );
		$this->assertPaymentWasStored( DirectDebitPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 400 ),
			PaymentInterval::Quarterly,
			new Iban( DirectDebitBankData::IBAN ),
			DirectDebitBankData::BIC
		) );
	}

	public function testCreateSofortPaymentFailsOnUnsupportedInterval(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: PaymentInterval::Monthly->value,
			paymentType: 'SUB',
			transferCodePrefix: 'TestPrefix'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertStringContainsString( 'Sofort payments cannot be recurring', $result->errorMessage );
	}

	public function testCreatePaymentWithInvalidIntervalFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 1000,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertStringContainsString( 'Interval', $result->errorMessage );
	}

	public function testCreatePaymentWithInvalidAmountFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: -500,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertStringContainsString( 'Amount', $result->errorMessage );
	}

	public function testCreatePaymentWithInvalidPaymentTypeFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 500,
			interval: 0,
			paymentType: 'TRA$HCOIN',
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertStringContainsString( 'payment type', $result->errorMessage );
	}

	public function testCreatePaymentWithFailingDomainValidationFails(): void {
		$useCase = $this->useCaseBuilder->build();

		$request = $this->newPaymentCreationRequest(
			amountInEuroCents: 500,
			interval: 0,
			paymentType: 'PPL',
			domainSpecificPaymentValidator: new FailingDomainSpecificValidator()
		);
		$result = $useCase->createPayment( $request );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertStringContainsString( 'domain check', $result->errorMessage );
	}

	public function testCreateDirectDebitPaymentWithInvalidIbanFails(): void {
		$useCase = $this->useCaseBuilder
			->withFailingIbanValidationUseCase()
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'BEZ',
			iban: DirectDebitBankData::IBAN,
			bic: DirectDebitBankData::BIC
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertEquals( "An invalid IBAN was provided", $result->errorMessage );
	}

	public function testPaymentResponseContainsURLFromURLGeneratorFactory(): void {
		$urlGeneratorFactory = $this->createStub( UrlGeneratorFactory::class );
		$urlGeneratorFactory->method( 'createURLGenerator' )->willReturn( new UrlGeneratorStub() );
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->withUrlGeneratorFactory( $urlGeneratorFactory )
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP',
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( UrlGeneratorStub::URL, $result->paymentCompletionUrl );
	}

	public function testPaymentProviderAdapterCanReplaceUrlGenerator(): void {
		$urlGeneratorFactory = $this->givenUrlGeneratorFactoryReturnsIncompleteUrlGenerator();
		$adapterStub = $this->createStub( PaymentProviderAdapter::class );
		$adapterStub->method( 'modifyPaymentUrlGenerator' )->willReturn( new UrlGeneratorStub() );
		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->withUrlGeneratorFactory( $urlGeneratorFactory )
			->withPaymentProviderAdapter( $adapterStub )
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP',
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( UrlGeneratorStub::URL, $result->paymentCompletionUrl );
	}

	public function testPaymentProviderCanReplacePaymentBeforeStoring(): void {
		$replacementPaymentId = 123;
		$paymentFromAdapter = new CreditCardPayment( $replacementPaymentId, Euro::newFromCents( 789 ), PaymentInterval::OneTime );
		$adapter = $this->createMock( PaymentProviderAdapter::class );
		$adapter->expects( $this->once() )
			->method( 'fetchAndStoreAdditionalData' )
			->willReturn( $paymentFromAdapter );

		$useCase = $this->useCaseBuilder
			->withIdGenerator( new SequentialPaymentIdRepository( self::PAYMENT_ID ) )
			->withPaymentRepositorySpy()
			->withPaymentProviderAdapter( $adapter )
			->build();

		$result = $useCase->createPayment( $this->newPaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP',
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( $replacementPaymentId, $result->paymentId );
		$repositorySpy = $this->useCaseBuilder->getPaymentRepository();
		$storedPayment = $repositorySpy->getPaymentById( $replacementPaymentId );
		$this->assertSame( $paymentFromAdapter, $storedPayment );
	}

	private function newPaymentCreationRequest(
		int $amountInEuroCents,
		int $interval,
		string $paymentType,
		string $iban = '',
		string $bic = '',
		string $transferCodePrefix = '',
		?DomainSpecificPaymentValidator $domainSpecificPaymentValidator = null
	): PaymentCreationRequest {
		return new PaymentCreationRequest(
			$amountInEuroCents,
			$interval,
			$paymentType,
			$domainSpecificPaymentValidator ?? new SucceedingDomainSpecificValidator(),
			DomainSpecificContextForTesting::create(),
			new FakeUrlAuthenticator(),
			$iban,
			$bic,
			$transferCodePrefix
		);
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
		$actualPayment = $this->useCaseBuilder->getPaymentRepository()->getPaymentById( self::PAYMENT_ID );
		$this->assertEquals( $expectedPayment, $actualPayment );
	}

	private function givenUrlGeneratorFactoryReturnsIncompleteUrlGenerator(): UrlGeneratorFactory {
		$urlGenerator = $this->createStub( PaymentCompletionURLGenerator::class );
		$urlGenerator->method( 'generateURL' )
			->willThrowException( new \LogicException( 'The "original" URL generator should be replaced by the payment provider adapter' ) );
		$urlGeneratorFactory = $this->createStub( UrlGeneratorFactory::class );
		$urlGeneratorFactory->method( 'createURLGenerator' )->willReturn( $urlGenerator );
		return $urlGeneratorFactory;
	}
}
