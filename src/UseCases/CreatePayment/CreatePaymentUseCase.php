<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Domain\TransferCodeGenerator;

class CreatePaymentUseCase {
	public function __construct(
		private PaymentIDRepository $idGenerator,
		private PaymentRepository $paymentRepository,
		private TransferCodeGenerator $paymentReferenceCodeGenerator ) {
	}

	public function createPayment( PaymentCreationRequest $request ): SuccessResponse|FailureResponse {
		$paymentOrFailure = $this->doCreatePayment( $request );

		if ( $paymentOrFailure instanceof FailureResponse ) {
			return $paymentOrFailure;
		}
		$payment = $paymentOrFailure;

		$this->paymentRepository->storePayment( $payment );
		return new SuccessResponse( $this->getNextIdOnce() );
	}

	private function getNextIdOnce(): int {
		static $id = null;
		if ( $id === null ) {
			$id = $this->idGenerator->getNewID();
		}
		return $id;
	}

	private function doCreatePayment( PaymentCreationRequest $request ): Payment|FailureResponse {
		try {
			return match ( $request->paymentType ) {
				'MCP' => $this->createCreditCardPayment( $request ),
				'PPL' => $this->createPayPalPayment( $request ),
				'SUB' => $this->createSofortPayment( $request ),
				default => new FailureResponse( "Invalid payment type: " . $request->paymentType ),
			};
		} catch ( PaymentCreationException $e ) {
			return new FailureResponse( $e->getMessage() );
		}
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return CreditCardPayment
	 * @throws PaymentCreationException
	 */
	private function createCreditCardPayment( PaymentCreationRequest $request ): CreditCardPayment {
		return new CreditCardPayment(
			$this->getNextIdOnce(),
			$this->createAmount( $request ),
			$this->createInterval( $request )
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return PayPalPayment
	 * @throws PaymentCreationException
	 */
	private function createPayPalPayment( PaymentCreationRequest $request ): PayPalPayment {
		return new PayPalPayment(
			$this->getNextIdOnce(),
			$this->createAmount( $request ),
			$this->createInterval( $request )
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return SofortPayment|FailureResponse
	 * @throws PaymentCreationException
	 */
	private function createSofortPayment( PaymentCreationRequest $request ): SofortPayment|FailureResponse {
		$paymentInterval = $this->createInterval( $request );
		if ( $paymentInterval !== PaymentInterval::OneTime ) {
			return new FailureResponse( "Sofort payment does not support recurring intervals (>0)." );
		}

		return new SofortPayment(
			$this->getNextIdOnce(),
			$this->createAmount( $request ),
			$paymentInterval,
			$this->paymentReferenceCodeGenerator->generateTransferCode( $request->transferCodePrefix )
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return Euro
	 * @throws PaymentCreationException
	 */
	private function createAmount( PaymentCreationRequest $request ): Euro {
		try {
			return Euro::newFromCents( $request->amountInEuroCents );
		} catch ( \InvalidArgumentException $e ) {
			throw new PaymentCreationException(
				"Invalid amount: %s" . $e->getMessage(),
				$e
			);
		}
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return PaymentInterval
	 * @throws PaymentCreationException
	 */
	private function createInterval( PaymentCreationRequest $request ): PaymentInterval {
		try {
			return PaymentInterval::from( $request->interval );
		} catch ( \ValueError $e ) {
			throw new PaymentCreationException(
				"Invalid amount: %s" . $e->getMessage(),
				$e
			);
		}
	}

}
