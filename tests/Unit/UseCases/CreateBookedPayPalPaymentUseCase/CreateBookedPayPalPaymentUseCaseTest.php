<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreateBookedPayPalPaymentUseCase;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal\PayPalVerificationService;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeTransactionIdFinder;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SequentialPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment\CreateBookedPayPalPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment\FailingPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment\SuccessResponse;

#[CoversClass( CreateBookedPayPalPaymentUseCase::class )]
#[CoversClass( FailingPaymentIdRepository::class )]
#[CoversClass( FailureResponse::class )]
#[CoversClass( SuccessResponse::class )]
class CreateBookedPayPalPaymentUseCaseTest extends TestCase {
	private const PAYMENT_ID = 5;

	public function testGivenSuccessfulVerification_paymentIsBooked(): void {
		$repository = new PaymentRepositorySpy( [] );
		$useCase = new CreateBookedPayPalPaymentUseCase(
			$repository,
			new SequentialPaymentIdRepository( self::PAYMENT_ID ),
			$this->makeSucceedingVerifier(),
			new FakeTransactionIdFinder()
		);

		$result = $useCase->bookNewPayment( 999, PayPalPaymentBookingData::newValidBookingData() );

		$storedPayment = $repository->getPaymentById( self::PAYMENT_ID );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
		$this->assertInstanceOf( PayPalPayment::class, $storedPayment );
		$this->assertTrue( $storedPayment->isBooked() );
		$this->assertEquals( 999, $storedPayment->getAmount()->getEuroCents() );
		$this->assertEquals( PaymentInterval::OneTime, $storedPayment->getInterval() );
	}

	public function testGivenFailingVerification_returnsFailureResponse(): void {
		$repository = $this->createMock( PaymentRepository::class );
		$repository->expects( $this->never() )->method( 'storePayment' );
		$useCase = new CreateBookedPayPalPaymentUseCase(
			$repository,
			new SequentialPaymentIdRepository( self::PAYMENT_ID ),
			$this->makeFailingVerifier(),
			new FakeTransactionIdFinder()
		);

		$result = $useCase->bookNewPayment( 999, PayPalPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testGivenInvalidPaymentAmount_returnsFailureResponse(): void {
		$repository = $this->createMock( PaymentRepository::class );
		$repository->expects( $this->never() )->method( 'storePayment' );
		$useCase = new CreateBookedPayPalPaymentUseCase(
			$repository,
			new SequentialPaymentIdRepository( self::PAYMENT_ID ),
			$this->makeSucceedingVerifier(),
			new FakeTransactionIdFinder()
		);

		$result = $useCase->bookNewPayment( -5, PayPalPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testGivenTransactionDataThatWasPreviouslyProcessed_returnsFailureResponse(): void {
		$repository = $this->createMock( PaymentRepository::class );
		$repository->expects( $this->never() )->method( 'storePayment' );
		$useCase = new CreateBookedPayPalPaymentUseCase(
			$repository,
			new SequentialPaymentIdRepository( self::PAYMENT_ID ),
			$this->makeSucceedingVerifier(),
			new FakeTransactionIdFinder( [ PayPalPaymentBookingData::TRANSACTION_ID => self::PAYMENT_ID ] )
		);

		$result = $useCase->bookNewPayment( 999, PayPalPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertSame( 'This transaction was already processed', $result->message );
	}

	private function makeSucceedingVerifier(): PayPalVerificationService {
		$validator = $this->createMock( PayPalVerificationService::class );
		$validator->method( 'validate' )->willReturn( VerificationResponse::newSuccessResponse() );
		return $validator;
	}

	private function makeFailingVerifier(): PayPalVerificationService {
		$validator = $this->createMock( PayPalVerificationService::class );
		$validator->method( 'validate' )->willReturn( VerificationResponse::newFailureResponse( '' ) );
		return $validator;
	}
}
