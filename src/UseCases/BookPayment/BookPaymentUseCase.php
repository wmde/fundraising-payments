<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\AssociablePayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookablePayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class BookPaymentUseCase {

	public function __construct(
		private PaymentRepository $repository,
		private PaymentIDRepository $idGenerator
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

		if ( $payment->isCompleted() && $payment instanceof AssociablePayment ) {
			return $this->createFollowupPayment( $payment, $transactionData );
		} elseif ( $payment->isCompleted() ) {
			return new FailureResponse( 'Payment is already completed' );
		}

		return $this->bookAndStorePayment( $payment, $transactionData );
	}

	/**
	 * @param AssociablePayment<Payment&BookablePayment>&Payment&BookablePayment $parentPayment
	 * @param array<string,mixed> $transactionData
	 * @return SuccessResponse|FailureResponse
	 */
	private function createFollowupPayment( Payment & AssociablePayment & BookablePayment $parentPayment, array $transactionData ): SuccessResponse|FailureResponse {
		$childPayment = $parentPayment->createFollowUpPayment( $this->idGenerator->getNewID() );
		$result = $this->bookAndStorePayment( $childPayment, $transactionData );
		if ( $result instanceof FailureResponse ) {
			return $result;
		}
		return new FollowUpSuccessResponse( $parentPayment->getId(), $childPayment->getId() );
	}

	/**
	 * @param Payment&BookablePayment $payment
	 * @param array<string,mixed> $transactionData
	 * @return SuccessResponse|FailureResponse
	 */
	private function bookAndStorePayment( Payment & BookablePayment $payment, array $transactionData ): SuccessResponse|FailureResponse {
		try {
			$payment->bookPayment( $transactionData );
		} catch ( \InvalidArgumentException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		$this->repository->storePayment( $payment );

		return new SuccessResponse();
	}

}
