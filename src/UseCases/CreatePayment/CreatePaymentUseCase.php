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
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\UrlGeneratorFactory;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;

class CreatePaymentUseCase {
	public function __construct(
		private PaymentIDRepository $idGenerator,
		private PaymentRepository $paymentRepository,
		private PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator,
		private PaymentValidator $paymentValidator,
		private ValidateIbanUseCase $validateIbanUseCase,
		private UrlGeneratorFactory $paymentURLFactory
	) {
	}

	public function createPayment( PaymentCreationRequest $request ): SuccessResponse|FailureResponse {
		$validationResult = $this->paymentValidator->validatePaymentData( $request->amountInEuroCents, $request->interval, $request->paymentType, $request->getDomainSpecificPaymentValidator() );
		if ( !$validationResult->isSuccessful() ) {
			return new FailureResponse( $validationResult->getValidationErrors()[0]->getMessageIdentifier() );
		}

		try {
			$payment = $this->tryCreatePayment( $request );
		} catch ( PaymentCreationException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		$paymentProviderURLGenerator = $this->createPaymentProviderURLGenerator( $payment );

		$this->paymentRepository->storePayment( $payment );
		return new SuccessResponse( $payment->getId(), $paymentProviderURLGenerator, $payment->isCompleted() );
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return Payment
	 * @throws PaymentCreationException
	 */
	private function tryCreatePayment( PaymentCreationRequest $request ): Payment {
		return match ( PaymentType::tryFrom( $request->paymentType ) ) {
			PaymentType::CreditCard => $this->createCreditCardPayment( $request ),
			PaymentType::Paypal => $this->createPayPalPayment( $request ),
			PaymentType::Sofort => $this->createSofortPayment( $request ),
			PaymentType::BankTransfer => $this->createBankTransferPayment( $request ),
			PaymentType::DirectDebit => $this->createDirectDebitPayment( $request ),
			default => throw new \LogicException( sprintf(
				'Invalid payment type not caught by %s: %s', PaymentValidator::class, $request->paymentType
			) )
		};
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return CreditCardPayment
	 */
	private function createCreditCardPayment( PaymentCreationRequest $request ): CreditCardPayment {
		return new CreditCardPayment(
			$this->idGenerator->getNewID(),
			Euro::newFromCents( $request->amountInEuroCents ),
			PaymentInterval::from( $request->interval )
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return PayPalPayment
	 */
	private function createPayPalPayment( PaymentCreationRequest $request ): PayPalPayment {
		return new PayPalPayment(
			$this->idGenerator->getNewID(),
			Euro::newFromCents( $request->amountInEuroCents ),
			PaymentInterval::from( $request->interval )
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return SofortPayment
	 * @throws PaymentCreationException
	 */
	private function createSofortPayment( PaymentCreationRequest $request ): SofortPayment {
		$paymentInterval = PaymentInterval::from( $request->interval );
		if ( $paymentInterval !== PaymentInterval::OneTime ) {
			throw new PaymentCreationException( "Sofort payment does not support recurring intervals (>0)." );
		}

		return SofortPayment::create(
			$this->idGenerator->getNewID(),
			Euro::newFromCents( $request->amountInEuroCents ),
			$paymentInterval,
			$this->paymentReferenceCodeGenerator->newPaymentReference( $request->transferCodePrefix )
		);
	}

	/**
	 * @param PaymentCreationRequest $request
	 * @return BankTransferPayment
	 */
	private function createBankTransferPayment( PaymentCreationRequest $request ): BankTransferPayment {
		return BankTransferPayment::create(
			$this->idGenerator->getNewID(),
			Euro::newFromCents( $request->amountInEuroCents ),
			PaymentInterval::from( $request->interval ),
			$this->paymentReferenceCodeGenerator->newPaymentReference( $request->transferCodePrefix )
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
			$this->idGenerator->getNewID(),
			Euro::newFromCents( $request->amountInEuroCents ),
			PaymentInterval::from( $request->interval ),
			new Iban( $request->iban ),
			$request->bic
		);
	}

	private function createPaymentProviderURLGenerator( Payment $payment ): PaymentProviderURLGenerator {
		return $this->paymentURLFactory->createURLGenerator( $payment );
	}
}
