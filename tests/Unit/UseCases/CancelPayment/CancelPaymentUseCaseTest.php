<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CancelPayment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\SuccessResponse;

#[CoversClass( CancelPaymentUseCase::class )]
#[CoversClass( SuccessResponse::class )]
#[CoversClass( FailureResponse::class )]
class CancelPaymentUseCaseTest extends TestCase {

	public function testCancelsPayment(): void {
		$payment = $this->makeDirectDebitPayment();
		$repository = $this->createMock( PaymentRepository::class );
		$repository->method( 'getPaymentById' )->willReturn( $payment );
		$repository->expects( $this->once() )
			->method( 'storePayment' )
			->with( $payment );

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->cancelPayment( 1 );

		$this->assertTrue( $payment->isCancelled() );
		$this->assertInstanceOf( SuccessResponse::class, $response );
		$this->assertTrue( $response->paymentIsCompleted );
	}

	public function testCancelCanceledPaymentReturnsFailureResponse(): void {
		$payment = $this->makeCancelledDirectDebitPayment();
		$repository = new PaymentRepositorySpy( [ 1 => $payment ] );

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->cancelPayment( 1 );

		$this->assertInstanceOf( FailureResponse::class, $response );
	}

	public function testRestoresPayment(): void {
		$payment = $this->makeCancelledDirectDebitPayment();
		$repository = $this->createMock( PaymentRepository::class );
		$repository->method( 'getPaymentById' )->willReturn( $payment );
		$repository->expects( $this->once() )
			->method( 'storePayment' )
			->with( $payment );

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->restorePayment( 1 );

		$this->assertFalse( $payment->isCancelled() );
		$this->assertInstanceOf( SuccessResponse::class, $response );
		$this->assertTrue( $response->paymentIsCompleted );
	}

	public function testRestoreUncanceledPaymentReturnsFailureResponse(): void {
		$payment = $this->makeDirectDebitPayment();
		$repository = new PaymentRepositorySpy( [ 1 => $payment ] );

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->restorePayment( 1 );

		$this->assertInstanceOf( FailureResponse::class, $response );
	}

	public function testCancelMissingPaymentThrowsException(): void {
		$repository = $this->createMock( PaymentRepository::class );
		$repository->method( 'getPaymentById' )->willThrowException(
			new PaymentNotFoundException( 'Me fail English, that\'s unpossible' )
		);

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->cancelPayment( 1 );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Me fail English, that\'s unpossible', $response->message );
	}

	public function testRestoreMissingPaymentThrowsException(): void {
		$repository = $this->createMock( PaymentRepository::class );
		$repository->method( 'getPaymentById' )->willThrowException(
			new PaymentNotFoundException( 'Me fail English, that\'s unpossible' )
		);

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->restorePayment( 1 );

		$this->assertInstanceOf( FailureResponse::class, $response );
		$this->assertSame( 'Me fail English, that\'s unpossible', $response->message );
	}

	public function testCancelNonCancellablePaymentReturnsFailureResponse(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 100 ), PaymentInterval::OneTime );
		$repository = new PaymentRepositorySpy( [ 1 => $payment ] );

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->cancelPayment( 1 );

		$this->assertInstanceOf( FailureResponse::class, $response );
	}

	public function testRestoreNonCancellablePaymentReturnsFailureResponse(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 100 ), PaymentInterval::OneTime );
		$repository = new PaymentRepositorySpy( [ 1 => $payment ] );

		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->restorePayment( 1 );

		$this->assertInstanceOf( FailureResponse::class, $response );
	}

	private function makeDirectDebitPayment(): DirectDebitPayment {
		return DirectDebitPayment::create(
			1,
			Euro::newFromCents( 100 ),
			PaymentInterval::OneTime,
			new Iban( DirectDebitBankData::IBAN ),
			DirectDebitBankData::BIC
		);
	}

	private function makeCancelledDirectDebitPayment(): DirectDebitPayment {
		$payment = $this->makeDirectDebitPayment();
		$payment->cancel();
		return $payment;
	}
}
