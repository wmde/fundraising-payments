<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;

class CreatePaymentUseCase {
	public function __construct(
		private PaymentIDRepository $idGenerator,
		private PaymentRepository $paymentRepository,
		private PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator,
		private ValidateIbanUseCase $validateIbanUseCase
	) {
	}

	public function createPayment( PaymentCreationRequest $request ): SuccessResponse|FailureResponse {
		try {
			$payment = $this->tryCreatePayment( $request );
		} catch ( PaymentCreationException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

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

	/**
	 * @param PaymentCreationRequest $request
	 * @return Payment
	 * @throws PaymentCreationException
	 */
	private function tryCreatePayment( PaymentCreationRequest $request ): Payment {
		return match ( $request->paymentType ) {
			'MCP' => $this->createCreditCardPayment( $request ),
			'PPL' => $this->createPayPalPayment( $request ),
			'SUB' => $this->createSofortPayment( $request ),
			'UEB' => $this->createBankTransferPayment( $request ),
			'BEZ' => $this->createDirectDebitPayment( $request ),
			default => throw new PaymentCreationException( 'Invalid payment type: ' . $request->paymentType )
		};
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
	 * @return SofortPayment
	 * @throws PaymentCreationException
	 */
	private function createSofortPayment( PaymentCreationRequest $request ): SofortPayment {
		$paymentInterval = $this->createInterval( $request );
		if ( $paymentInterval !== PaymentInterval::OneTime ) {
			throw new PaymentCreationException( "Sofort payment does not support recurring intervals (>0)." );
		}

		return new SofortPayment(
			$this->getNextIdOnce(),
			$this->createAmount( $request ),
			$paymentInterval,
			$this->paymentReferenceCodeGenerator->newPaymentReference( $request->transferCodePrefix )->getFormattedCode()
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return BankTransferPayment
	 * @throws PaymentCreationException
	 */
	private function createBankTransferPayment( PaymentCreationRequest $request ): BankTransferPayment {
		return new BankTransferPayment(
			$this->getNextIdOnce(),
			$this->createAmount( $request ),
			$this->createInterval( $request ),
			$this->paymentReferenceCodeGenerator->newPaymentReference( $request->transferCodePrefix )->getFormattedCode()
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return DirectDebitPayment
	 * @throws PaymentCreationException
	 */
	private function createDirectDebitPayment( PaymentCreationRequest $request ): DirectDebitPayment {
		if ( !$this->validateIbanUseCase->ibanIsValid( $request->iban ) ) {
			throw new PaymentCreationException( "An invalid Iban was provided" );
		}

		return DirectDebitPayment::create(
			$this->getNextIdOnce(),
			$this->createAmount( $request ),
			$this->createInterval( $request ),
			new Iban( $request->iban ),
			$request->bic
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
