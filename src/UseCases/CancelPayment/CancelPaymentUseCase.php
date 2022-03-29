<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CancelPayment;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\CancellablePayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class CancelPaymentUseCase {

	private PaymentRepository $repository;

	public function __construct( PaymentRepository $repository ) {
		$this->repository = $repository;
	}

	public function cancelPayment( int $paymentId ): SuccessResponse|FailureResponse {
		try {
			$payment = $this->repository->getPaymentById( $paymentId );
		} catch ( PaymentNotFoundException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		if ( !( $payment instanceof CancellablePayment ) ) {
			throw new \RuntimeException( 'Tried to cancel an non-cancellable payment' );
		}

		if ( !$payment->isCancellable() ) {
			return new FailureResponse( 'This payment is already cancelled' );
		}

		$payment->cancel();
		$this->repository->storePayment( $payment );

		return new SuccessResponse();
	}
}
