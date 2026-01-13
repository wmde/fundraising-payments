<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\BookPayment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\CreditCardPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeTransactionIdFinder;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\BookPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FollowUpSuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\SuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationService;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationServiceFactory;

#[CoversClass( BookPaymentUseCase::class )]
#[CoversClass( FollowUpSuccessResponse::class )]
#[CoversClass( SuccessResponse::class )]
#[CoversClass( FailureResponse::class )]
class BookPaymentUseCaseTest extends TestCase {

	private const PAYMENT_ID = 7;
	private const CHILD_PAYMENT_ID = 42;

	public function testPaymentGetsBookedAndStored(): void {
		$payment = $this->makeCreditCardPayment();
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willReturn( $payment );
		$repo->expects( $this->once() )
			->method( 'storePayment' )
			->with( $payment );

		$useCase = $this->makeBookPaymentUseCase( $repo );

		$request = CreditCardPaymentBookingData::newValidBookingData( 1122 );

		$response = $useCase->bookPayment( self::PAYMENT_ID, $request );

		$this->assertInstanceOf( SuccessResponse::class, $response );
		$this->assertFalse( $payment->canBeBooked( $request ) );
	}

	public function testBookingMissingPaymentWillReturnFailureResult(): void {
		$repo = $this->createStub( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willThrowException(
			new PaymentNotFoundException( 'Me fail English, that\'s unpossible' )
		);
		$useCase = $this->makeBookPaymentUseCase( $repo );

		$response = $useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Me fail English, that\'s unpossible', $response->message );
	}

	public function testBookingNonBookablePaymentsWillThrowException(): void {
		$payment = $this->makeDirectDebitPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = $this->makeBookPaymentUseCase( $repo );

		$this->expectException( RuntimeException::class );

		$useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );
	}

	public function testBookingBookedPaymentsWillReturnFailureResponse(): void {
		$idGenerator = $this->makePaymentIdGenerator();
		$payment = $this->makeCreditCardPayment();
		$payment->bookPayment( [ 'transactionId' => 'deadbeef', 'amount' => 1122 ], $idGenerator );
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase( $repo, $idGenerator, $this->makeSucceedingVerificationServiceFactory(), new FakeTransactionIdFinder() );

		$response = $useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Payment is already completed', $response->message );
	}

	public function testBookingWithInvalidPaymentDataWillReturnFailureResponse(): void {
		$payment = $this->makeCreditCardPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = $this->makeBookPaymentUseCase( $repo );

		$response = $useCase->bookPayment( self::PAYMENT_ID, [ 'faultyKey' => '' ] );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'transactionId was not provided', $response->message );
	}

	public function testBookedPaymentsThatAllowFollowups_CreateFollowUpPaymentsWhenTheyAreBooked(): void {
		$idGeneratorStub = $this->createStub( PaymentIdRepository::class );
		$idGeneratorStub->method( 'getNewId' )->willReturn( self::CHILD_PAYMENT_ID );
		$payment = $this->makeBookedPayPalPayment( $idGeneratorStub );
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase( $repo, $idGeneratorStub, $this->makeSucceedingVerificationServiceFactory(), new FakeTransactionIdFinder() );

		$response = $useCase->bookPayment(
			self::PAYMENT_ID,
			PayPalPaymentBookingData::newValidBookingData()
		);

		$childPayment = $repo->payments[self::CHILD_PAYMENT_ID];
		$this->assertInstanceOf( PayPalPayment::class, $childPayment );
		$this->assertFalse( $childPayment->canBeBooked( PayPalPaymentBookingData::newValidBookingData() ) );
		$this->assertInstanceOf( FollowUpSuccessResponse::class, $response );
		$this->assertSame( self::PAYMENT_ID, $response->parentPaymentId );
		$this->assertSame( self::CHILD_PAYMENT_ID, $response->childPaymentId );
	}

	public function testInvalidBookingDataReturnsFailureResponseForFollowupPayments(): void {
		$payment = $this->makeBookedPayPalPayment( $this->makePaymentIdGenerator() );
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = $this->makeBookPaymentUseCase( $repo );

		$response = $useCase->bookPayment( self::PAYMENT_ID, [] );

		$this->assertInstanceOf( FailureResponse::class, $response );
	}

	public function testExternalValidationServiceFailureReturnsFailureResponse(): void {
		$payment = $this->makeCreditCardPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase(
			$repo,
			$this->makePaymentIdGenerator(),
			$this->makeFailingVerificationServiceFactory( 'I failed' ),
			new FakeTransactionIdFinder()
		);

		$response = $useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertEquals( 'I failed', $response->message );
	}

	public function testGivenExistingPayPalTransactionId_bookingWillFail(): void {
		$idGeneratorStub = $this->makePaymentIdGenerator();
		$payment = $this->makeBookedPayPalPayment( $idGeneratorStub );
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase(
			$repo,
			$idGeneratorStub,
			$this->makeSucceedingVerificationServiceFactory(),
			new FakeTransactionIdFinder( [ PayPalPaymentBookingData::TRANSACTION_ID => self::PAYMENT_ID ] )
		);

		$response = $useCase->bookPayment(
			self::PAYMENT_ID,
			PayPalPaymentBookingData::newValidBookingData()
		);

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Payment is already completed', $response->message );
	}

	private function makeBookPaymentUseCase( PaymentRepository $repo ): BookPaymentUseCase {
		return new BookPaymentUseCase(
			$repo,
			$this->makePaymentIdGenerator(),
			$this->makeSucceedingVerificationServiceFactory(),
			new FakeTransactionIdFinder()
		);
	}

	private function makeSucceedingVerificationServiceFactory(): VerificationServiceFactory {
		$validator = $this->createConfiguredStub(
			VerificationService::class,
			[ 'validate' => VerificationResponse::newSuccessResponse() ]
		);

		return $this->createConfiguredStub(
			VerificationServiceFactory::class,
			[ 'create' => $validator ]
		);
	}

	private function makeFailingVerificationServiceFactory( string $failureMessage ): VerificationServiceFactory {
		$validator = $this->createConfiguredStub(
			VerificationService::class,
			[ 'validate' => VerificationResponse::newFailureResponse( $failureMessage ) ]
		);

		return $this->createConfiguredStub(
			VerificationServiceFactory::class,
			[ 'create' => $validator ]
		);
	}

	private function makeBookedPayPalPayment( PaymentIdRepository $idGenerator ): PayPalPayment {
		$payment = new PayPalPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), $idGenerator );
		return $payment;
	}

	private function makeCreditCardPayment(): CreditCardPayment {
		return new CreditCardPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
	}

	private function makePaymentIdGenerator(): PaymentIdRepository {
		return new DummyPaymentIdRepository();
	}

	private function makeDirectDebitPayment(): DirectDebitPayment {
		return DirectDebitPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly,
			new Iban( DirectDebitBankData::IBAN ),
			DirectDebitBankData::BIC
		);
	}

}
