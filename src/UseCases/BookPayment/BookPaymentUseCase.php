<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookablePayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class BookPaymentUseCase {

	public function __construct(
		private PaymentRepository $repository,
		private PaymentIDRepository $idGenerator,
		private VerificationServiceFactory $verificationServiceFactory
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

		if ( !$payment->canBeBooked( $transactionData ) ) {
			return new FailureResponse( 'Payment is already completed' );
		}

		$verificationResponse = $this->validateWithExternalService( $payment, $transactionData );
		if ( !$verificationResponse->isValid() ) {
			return new FailureResponse( $verificationResponse->getMessage() );
		}

		try {
			$bookedPayment = $payment->bookPayment( $transactionData, $this->idGenerator );
		} catch ( \InvalidArgumentException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		$this->repository->storePayment( $bookedPayment );

		if ( $bookedPayment !== $payment ) {
			return new FollowUpSuccessResponse( $payment->getId(), $bookedPayment->getId() );
		}

		return new SuccessResponse();
	}

	/**
	 * @param Payment $payment
	 * @param array<string,mixed> $transactionData
	 *
	 * @return VerificationResponse
	 */
	private function validateWithExternalService( Payment $payment, array $transactionData ): VerificationResponse {
		return $this->verificationServiceFactory->create( $payment )
			->validate( $transactionData );
	}
}
