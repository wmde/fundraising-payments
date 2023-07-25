<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookablePayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;

class BookPaymentUseCase {

	public function __construct(
		private PaymentRepository $repository,
		private PaymentIdRepository $idGenerator,
		private VerificationServiceFactory $verificationServiceFactory,
		private TransactionIdFinder $transactionIdFinder,
	) {
	}

	/**
	 * @param int $paymentId
	 * @param array<string,scalar> $transactionData
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

		if ( $this->paymentWasAlreadyBooked( $payment, $transactionData ) ) {
			return FailureResponse::newAlreadyCompletedResponse();
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
	 * @param array<string,scalar> $transactionData
	 *
	 * @return VerificationResponse
	 */
	private function validateWithExternalService( Payment $payment, array $transactionData ): VerificationResponse {
		return $this->verificationServiceFactory->create( $payment )
			->validate( $transactionData );
	}

	/**
	 * @param BookablePayment $payment
	 * @param array<string,scalar> $transactionData
	 * @return bool
	 */
	private function paymentWasAlreadyBooked( BookablePayment $payment, array $transactionData ): bool {
		$wasAlreadyBooked = false;
		if ( $payment instanceof PayPalPayment ) {
			$wasAlreadyBooked = $this->paypalPaymentWasAlreadyBooked( $payment, $transactionData );
		}

		return $wasAlreadyBooked || !$payment->canBeBooked( $transactionData );
	}

	/**
	 * @param PayPalPayment $payment
	 * @param array<string,scalar> $transactionData
	 * @return bool
	 */
	private function paypalPaymentWasAlreadyBooked( PayPalPayment $payment, array $transactionData ): bool {
		if ( empty( $transactionData[PayPalBookingTransformer::TRANSACTION_ID_KEY] ) ) {
			return false;
		}
		$currentTransactionId = $transactionData[PayPalBookingTransformer::TRANSACTION_ID_KEY];
		$previousTransactionIds = $this->transactionIdFinder->getAllTransactionIDs( $payment );

		return !empty( $previousTransactionIds[$currentTransactionId] );
	}
}
