<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\BookPayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\CreditCardPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\BookPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FollowUpSuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\SuccessResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\BookPaymentUseCase
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FollowUpSuccessResponse
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\SuccessResponse
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse
 */
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

		$useCase = new BookPaymentUseCase( $repo, $this->makePaymentIdGenerator() );
		$response = $useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( SuccessResponse::class, $response );
		$this->assertFalse( $payment->canBeBooked( CreditCardPaymentBookingData::newValidBookingData() ) );
	}

	public function testBookingMissingPaymentWillReturnFailureResult(): void {
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willThrowException(
			new PaymentNotFoundException( 'Me fail English, that\'s unpossible' )
		);
		$useCase = new BookPaymentUseCase( $repo, $this->makePaymentIdGenerator() );

		$response = $useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Me fail English, that\'s unpossible', $response->message );
	}

	public function testBookingNonBookablePaymentsWillThrowException(): void {
		$payment = $this->makeDirectDebitPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase( $repo, $this->makePaymentIdGenerator() );

		$this->expectException( \RuntimeException::class );

		$useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );
	}

	public function testBookingBookedPaymentsWillReturnFailureResponse(): void {
		$payment = $this->makeCreditCardPayment();
		$payment->bookPayment( [ 'transactionId' => 'deadbeef' ] );
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase( $repo, $this->makePaymentIdGenerator() );

		$response = $useCase->bookPayment( self::PAYMENT_ID, CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Payment is already completed', $response->message );
	}

	public function testBookingWithInvalidPaymentDataWillReturnFailureResponse(): void {
		$payment = $this->makeCreditCardPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase( $repo, $this->makePaymentIdGenerator() );

		$response = $useCase->bookPayment( self::PAYMENT_ID, [ 'faultyKey' => '' ] );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'transactionId was not provided', $response->message );
	}

	public function testBookedPaymentsThatAllowFollowups_CreateFollowUpPaymentsWhenTheyAreBooked(): void {
		$payment = $this->makeBookedPayPalPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$idGeneratorStub = $this->createStub( PaymentIDRepository::class );
		$idGeneratorStub->method( 'getNewID' )->willReturn( self::CHILD_PAYMENT_ID );
		$useCase = new BookPaymentUseCase( $repo, $idGeneratorStub );

		$response = $useCase->bookPayment(
			self::PAYMENT_ID,
			PayPalPaymentBookingData::newValidBookingData()
		);

		$childPayment = $repo->payments[ self::CHILD_PAYMENT_ID ];
		$this->assertInstanceOf( PayPalPayment::class, $childPayment );
		$this->assertFalse( $childPayment->canBeBooked( PayPalPaymentBookingData::newValidBookingData() ) );
		$this->assertInstanceOf( FollowUpSuccessResponse::class, $response );
		$this->assertSame( self::PAYMENT_ID, $response->parentPaymentId );
		$this->assertSame( self::CHILD_PAYMENT_ID, $response->childPaymentId );
	}

	public function testInvalidBookingDataReturnsFailureResponseForFollowupPayments(): void {
		$payment = $this->makeBookedPayPalPayment();
		$repo = new PaymentRepositorySpy( [ self::PAYMENT_ID => $payment ] );
		$useCase = new BookPaymentUseCase( $repo, $this->makePaymentIdGenerator() );

		$response = $useCase->bookPayment( self::PAYMENT_ID, [] );

		$this->assertInstanceOf( FailureResponse::class, $response );
	}

	private function makeBookedPayPalPayment(): PayPalPayment {
		$payment = new PayPalPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData() );
		return $payment;
	}

	private function makeCreditCardPayment(): CreditCardPayment {
		return new CreditCardPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
	}

	private function makePaymentIdGenerator(): PaymentIDRepository {
		return $this->createStub( PaymentIDRepository::class );
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
