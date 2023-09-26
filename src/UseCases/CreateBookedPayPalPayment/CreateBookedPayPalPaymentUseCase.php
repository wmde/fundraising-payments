<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationService;

/**
 * This use case will be used to book incoming PayPal payments without any reference where we can look up a payment ID.
 *
 * This happens when a user does **not** use the Fundraising App, but instead uses the
 * PayPal app/website to send money to the donation email address of WMDE.
 *
 * Currently, the code outside the payment bounded context will use this to create a donation.
 * You can search for "PayPal" in the donation bounded context to find the call for this use case.
 */
class CreateBookedPayPalPaymentUseCase {
	public function __construct(
		private readonly PaymentRepository $repository,
		private readonly PaymentIdRepository $idGenerator,
		private readonly VerificationService $verificationService,
		private readonly TransactionIdFinder $transactionIdFinder
	) {
	}

	/**
	 * @param int $amountInCents
	 * @param array<string,scalar> $transactionData
	 * @return SuccessResponse|FailureResponse
	 */
	public function bookNewPayment( int $amountInCents, array $transactionData ): SuccessResponse|FailureResponse {
		try {
			$parsedAmount = Euro::newFromCents( $amountInCents );
		} catch ( \InvalidArgumentException $exception ) {
			return new FailureResponse( $exception->getMessage() );
		}

		if ( $this->transactionWasAlreadyProcessed( $transactionData ) ) {
			return new FailureResponse( 'This transaction was already processed' );
		}

		$payment = new PayPalPayment( $this->idGenerator->getNewId(), $parsedAmount, PaymentInterval::OneTime );
		$verificationResponse = $this->verificationService->validate( $transactionData );
		if ( !$verificationResponse->isValid() ) {
			return new FailureResponse( $verificationResponse->getMessage() );
		}

		$payment->bookPayment(
			$transactionData,
			new FailingPaymentIdRepository( "Immediately booked PayPal payments must not create followup payments." )
		);
		$this->repository->storePayment( $payment );

		return new SuccessResponse( $payment->getId() );
	}

	/**
	 * @param array<string,scalar> $transactionData
	 * @return bool
	 */
	private function transactionWasAlreadyProcessed( array $transactionData ): bool {
		// If the transaction data does not contain a transaction key, the transformer will fail anyway,
		// so we allow empty here to get to the point where the transformer and our try/catch handles the error
		if ( empty( $transactionData[PayPalBookingTransformer::TRANSACTION_ID_KEY] ) ) {
			return false;
		}
		$transactionId = strval( $transactionData[PayPalBookingTransformer::TRANSACTION_ID_KEY] );
		return $this->transactionIdFinder->transactionIdExists( $transactionId );
	}

}
