<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal\PayPalVerificationService;

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
		private PaymentRepository $repository,
		private PaymentIDRepository $idGenerator,
		private PayPalVerificationService $verificationService
	) {
	}

	/**
	 * @param int $amountInCents
	 * @param array<string,mixed> $transactionData
	 * @return SuccessResponse|FailureResponse
	 */
	public function bookNewPayment( int $amountInCents, array $transactionData ): SuccessResponse|FailureResponse {
		try{
			$parsedAmount = Euro::newFromCents( $amountInCents );
		} catch ( \InvalidArgumentException $exception ) {
			return new FailureResponse( $exception->getMessage() );
		}

		$payment = new PayPalPayment( $this->idGenerator->getNewID(), $parsedAmount, PaymentInterval::OneTime );
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

}
