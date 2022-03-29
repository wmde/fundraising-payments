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
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\BookPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\SuccessResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\BookPaymentUseCase
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\SuccessResponse
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse
 */
class BookPaymentUseCaseTest extends TestCase {

	private const PAYMENT_ID = 7;

	public function testPaymentGetsBookedAndStored(): void {
		$payment = new CreditCardPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willReturn( $payment );
		$repo->expects( $this->once() )
			->method( 'storePayment' )
			->with( $payment );

		$useCase = new BookPaymentUseCase( $repo );
		$response = $useCase->bookPayment( self::PAYMENT_ID, [ 'transactionId' => 'deadbeef' ] );

		$this->assertTrue( $payment->isCompleted() );
		$this->assertInstanceOf( SuccessResponse::class, $response );
	}

	public function testBookingMissingPaymentWillReturnFailureResult(): void {
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willThrowException( new PaymentNotFoundException( 'Me fail English, that\'s unpossible' ) );

		$useCase = new BookPaymentUseCase( $repo );
		$response = $useCase->bookPayment( self::PAYMENT_ID, [ 'transactionId' => 'deadbeef' ] );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Me fail English, that\'s unpossible', $response->message );
	}

	public function testBookingNonBookablePaymentsWillThrowException(): void {
		$payment = DirectDebitPayment::create(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly,
			new Iban( DirectDebitBankData::IBAN ),
			DirectDebitBankData::BIC
		);
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willReturn( $payment );

		$useCase = new BookPaymentUseCase( $repo );

		$this->expectException( \RuntimeException::class );

		$useCase->bookPayment( self::PAYMENT_ID, [ 'transactionId' => 'deadbeef' ] );
	}

	public function testBookingBookedPaymentsWillReturnFailureResponse(): void {
		$payment = new CreditCardPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
		$payment->bookPayment( [ 'transactionId' => 'deadbeef' ] );
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willReturn( $payment );

		$useCase = new BookPaymentUseCase( $repo );

		$response = $useCase->bookPayment( self::PAYMENT_ID, [ 'transactionId' => 'deadbeef' ] );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Payment is already completed', $response->message );
	}

	public function testBookingWithInvalidPaymentDataWillReturnFailureResponse(): void {
		$payment = new CreditCardPayment(
			self::PAYMENT_ID,
			Euro::newFromCents( 1122 ),
			PaymentInterval::Quarterly
		);
		$repo = $this->createMock( PaymentRepository::class );
		$repo->method( 'getPaymentById' )->willReturn( $payment );

		$useCase = new BookPaymentUseCase( $repo );

		$response = $useCase->bookPayment( self::PAYMENT_ID, [ 'faultyKey' => '' ] );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'transactionId was not provided', $response->message );
	}

}
