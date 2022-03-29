<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CancelPayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
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

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\SuccessResponse
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\FailureResponse
 */
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
	}

	public function testCancelCanceledPaymentReturnsFailureResponse(): void {
		$payment = $this->makeDirectDebitPayment();
		$repository = new PaymentRepositorySpy( [ 1 => $payment ] );

		$payment->cancel();
		$useCase = new CancelPaymentUseCase( $repository );
		$response = $useCase->cancelPayment( 1 );

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

	public function testCancelNonCancellablePaymentThrowsException(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 100 ), PaymentInterval::OneTime );
		$repository = new PaymentRepositorySpy( [ 1 => $payment ] );

		$useCase = new CancelPaymentUseCase( $repository );

		$this->expectException( \RuntimeException::class );
		$useCase->cancelPayment( 1 );
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
}
