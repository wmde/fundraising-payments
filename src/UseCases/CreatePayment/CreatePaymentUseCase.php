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
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\Fundraising\PaymentContext\Services\UrlGeneratorFactory;
use WMDE\Fundraising\PaymentContext\UseCases\BankDataFailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;

class CreatePaymentUseCase {
	public function __construct(
		private PaymentIdRepository $idGenerator,
		private PaymentRepository $paymentRepository,
		private PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator,
		private PaymentValidator $paymentValidator,
		private ValidateIbanUseCase $validateIbanUseCase,
		private UrlGeneratorFactory $paymentURLFactory,
		private PaymentProviderAdapterFactory $paymentProviderAdapterFactory
	) {
	}

	public function createPayment( PaymentCreationRequest $request ): SuccessResponse|FailureResponse {
		$validationResult = $this->paymentValidator->validatePaymentData( $request->amountInEuroCents, $request->interval, $request->paymentType, $request->domainSpecificPaymentValidator );
		if ( !$validationResult->isSuccessful() ) {
			return new FailureResponse( $validationResult->getValidationErrors()[0]->getMessageIdentifier() );
		}

		try {
			$payment = $this->tryCreatePayment( $request->getParameters() );
		} catch ( PaymentCreationException $e ) {
			return new FailureResponse( $e->getMessage() );
		}

		$paymentProvider = $this->paymentProviderAdapterFactory->createProvider( $payment, $request->urlAuthenticator );

		// payment providers may modify the payment or store payment-adjacent data
		// (e.g. PayPal payment IDs)
		$payment = $paymentProvider->fetchAndStoreAdditionalData( $payment, $request->domainSpecificContext );

		$this->paymentRepository->storePayment( $payment );

		return new SuccessResponse(
			$payment->getId(),
			$this->generatePaymentCompletionUrl( $payment, $paymentProvider, $request ),
			$payment->isCompleted()
		);
	}

	/**
	 * @param PaymentParameters $parameters
	 * @return Payment
	 * @throws PaymentCreationException
	 */
	private function tryCreatePayment( PaymentParameters $parameters ): Payment {
		return match ( PaymentType::tryFrom( $parameters->paymentType ) ) {
			PaymentType::CreditCard => $this->createCreditCardPayment( $parameters ),
			PaymentType::Paypal => $this->createPayPalPayment( $parameters ),
			PaymentType::Sofort => $this->createSofortPayment( $parameters ),
			PaymentType::BankTransfer => $this->createBankTransferPayment( $parameters ),
			PaymentType::DirectDebit => $this->createDirectDebitPayment( $parameters ),
			default => throw new \LogicException( sprintf(
				'Invalid payment type not caught by %s: %s', PaymentValidator::class, $parameters->paymentType
			) )
		};
	}

	/**
	 * @param PaymentParameters $parameters
	 * @return CreditCardPayment
	 */
	private function createCreditCardPayment( PaymentParameters $parameters ): CreditCardPayment {
		return new CreditCardPayment(
			$this->idGenerator->getNewId(),
			Euro::newFromCents( $parameters->amountInEuroCents ),
			PaymentInterval::from( $parameters->interval )
		);
	}

	/**
	 * @param PaymentParameters $parameters
	 * @return PayPalPayment
	 */
	private function createPayPalPayment( PaymentParameters $parameters ): PayPalPayment {
		return new PayPalPayment(
			$this->idGenerator->getNewId(),
			Euro::newFromCents( $parameters->amountInEuroCents ),
			PaymentInterval::from( $parameters->interval )
		);
	}

	private function createSofortPayment( PaymentParameters $parameters ): SofortPayment {
		$paymentInterval = PaymentInterval::from( $parameters->interval );
		// This check is a belt-and-suspenders approach to make sure the validator is working and was called
		// before this method. It should never be triggered.
		if ( $paymentInterval->isRecurring() ) {
			throw new \LogicException( "The validator did not catch the recurring payment, please check your code" );
		}

		return SofortPayment::create(
			$this->idGenerator->getNewId(),
			Euro::newFromCents( $parameters->amountInEuroCents ),
			$paymentInterval,
			$this->paymentReferenceCodeGenerator->newPaymentReference( $parameters->transferCodePrefix )
		);
	}

	/**
	 * @param PaymentParameters $parameters
	 * @return BankTransferPayment
	 */
	private function createBankTransferPayment( PaymentParameters $parameters ): BankTransferPayment {
		return BankTransferPayment::create(
			$this->idGenerator->getNewId(),
			Euro::newFromCents( $parameters->amountInEuroCents ),
			PaymentInterval::from( $parameters->interval ),
			$this->paymentReferenceCodeGenerator->newPaymentReference( $parameters->transferCodePrefix )
		);
	}

	/**
	 * @param PaymentParameters $parameters
	 * @return DirectDebitPayment
	 * @throws PaymentCreationException
	 */
	private function createDirectDebitPayment( PaymentParameters $parameters ): DirectDebitPayment {
		if ( $this->validateIbanUseCase->ibanIsValid( $parameters->iban ) instanceof BankDataFailureResponse ) {
			throw new PaymentCreationException( "An invalid IBAN was provided" );
		}

		return DirectDebitPayment::create(
			$this->idGenerator->getNewId(),
			Euro::newFromCents( $parameters->amountInEuroCents ),
			PaymentInterval::from( $parameters->interval ),
			new Iban( $parameters->iban ),
			$parameters->bic
		);
	}

	private function generatePaymentCompletionUrl(
		Payment $payment,
		PaymentProviderAdapter $paymentProvider,
		PaymentCreationRequest $request,
	): string {
		$domainSpecificContext = $request->domainSpecificContext;
		$paymentProviderURLGenerator = $this->paymentURLFactory->createURLGenerator( $payment, $request->urlAuthenticator );
		$paymentProviderURLGenerator = $paymentProvider->modifyPaymentUrlGenerator( $paymentProviderURLGenerator, $domainSpecificContext );
		return $paymentProviderURLGenerator->generateURL( $domainSpecificContext->getRequestContextForUrlGenerator() );
	}
}
