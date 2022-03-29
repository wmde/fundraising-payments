<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookablePayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class BookPaymentUseCase {

	public function __construct(
		private PaymentRepository $repository
	) {
	}

	/**
	 * @param int $paymentId
	 * @param array<string,mixed> $transactionData
	 *
	 * @return SuccessResponse|FailureResponse
	 */
	public function bookPayment( int $paymentId, array $transactionData ): SuccessResponse|FailureResponse {
		try {
			$payment = $this->repository->getPaymentById( $paymentId );
		} catch ( PaymentNotFoundException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		if ( !( $payment instanceof BookablePayment ) ) {
			throw new \RuntimeException( 'Tried to book an non-bookable payment' );
		}
		if ( $payment->isCompleted() ) {
			return new FailureResponse( 'Payment is already completed' );
		}

		try {
			$payment->bookPayment( $transactionData );
		} catch ( \InvalidArgumentException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		$this->repository->storePayment( $payment );

		return new SuccessResponse();
	}

}
