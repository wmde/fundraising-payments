<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase
 */
class CreatePaymentUseCaseTest extends TestCase {
	private const CC_PAYMENT_ID = 2;

	public function testCreateCreditCardPayment(): void {
		$idGenerator = $this->createMock( PaymentIDRepository::class );
		$idGenerator->method( 'getNewID' )->willReturn( self::CC_PAYMENT_ID );
		$repo = $this->createMock( PaymentRepository::class );
		$repo->expects( $this->once() )->method( 'storePayment' )->with(
			new CreditCardPayment( self::CC_PAYMENT_ID, Euro::newFromCents( 100 ), PaymentInterval::OneTime )
		);
		$useCase = new CreatePaymentUseCase( $idGenerator, $repo );

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::CC_PAYMENT_ID, $result->paymentId );
	}

	public function testCreatePaymentWithInvalidIntervalFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeRejectingIdGenerator(),
			$this->makeRejectingRepository()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 1000,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidAmountFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeRejectingIdGenerator(),
			$this->makeRejectingRepository()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: -500,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidPaymentTypeFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeRejectingIdGenerator(),
			$this->makeRejectingRepository()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 500,
			interval: 0,
			paymentType: 'TRA$HCOIN',
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	private function makeRejectingIdGenerator(): PaymentIDRepository {
		$idGenerator = $this->createMock( PaymentIDRepository::class );
		$idGenerator->expects( $this->never() )->method( 'getNewID' );
		return $idGenerator;
	}

	private function makeRejectingRepository(): PaymentRepository {
		$repo = $this->createMock( PaymentRepository::class );
		$repo->expects( $this->never() )->method( 'storePayment' );
		return $repo;
	}
}
