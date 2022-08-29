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

		if ( !( $payment instanceof CancellablePayment ) || !$payment->isCancellable() ) {
			return new FailureResponse( 'This payment can\'t be canceled - it is already cancelled or does not support cancellation' );
		}

		$payment->cancel();
		$this->repository->storePayment( $payment );

		return new SuccessResponse();
	}
}
