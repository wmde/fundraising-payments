<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\UrlGeneratorFactory;
use WMDE\Fundraising\PaymentContext\Services\KontoCheck\KontoCheckBankDataGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FixedPaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingIbanValidator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DefaultPaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapter;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentProviderAdapterFactory;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;

class CreatePaymentUseCaseBuilder {
	private PaymentIdRepository $idGenerator;
	private PaymentRepository $repository;
	private PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator;
	private UrlGeneratorFactory $urlGeneratorFactory;
	private ValidateIbanUseCase $validateIbanUseCase;
	private PaymentValidator $paymentValidator;
	private PaymentProviderAdapterFactory $paymentProviderAdapterFactory;

	public function __construct() {
		$this->idGenerator = $this->makeIdGeneratorStub();
		$this->repository = $this->makeRepositoryStub();
		$this->paymentReferenceCodeGenerator = $this->makePaymentReferenceGeneratorStub();
		$this->urlGeneratorFactory = $this->makePaymentURLFactoryStub();
		$this->validateIbanUseCase = $this->makeFailingIbanUseCase();
		$this->paymentValidator = $this->makePaymentValidator();
		$this->paymentProviderAdapterFactory = $this->makePaymentProviderAdapterFactory();
	}

	public function build(): CreatePaymentUseCase {
		return new CreatePaymentUseCase(
			$this->idGenerator,
			$this->repository,
			$this->paymentReferenceCodeGenerator,
			$this->paymentValidator,
			$this->validateIbanUseCase,
			$this->urlGeneratorFactory,
			$this->paymentProviderAdapterFactory
		);
	}

	private function makeIdGeneratorStub(): PaymentIdRepository {
		return new class implements PaymentIdRepository {
			public function getNewId(): int {
				throw new \LogicException( 'Test case must not generate an ID' );
			}
		};
	}

	private function makeRepositoryStub(): PaymentRepository {
		return new class implements PaymentRepository {
			public function storePayment( Payment $payment ): void {
				throw new \LogicException( 'Test case must not store payment' );
			}

			public function getPaymentById( int $id ): Payment {
				throw new \LogicException( 'Test case must not read payment' );
			}
		};
	}

	private function makePaymentReferenceGeneratorStub(): PaymentReferenceCodeGenerator {
		return new FixedPaymentReferenceCodeGenerator( [] );
	}

	private function makePaymentURLFactoryStub(): UrlGeneratorFactory {
		return new class implements UrlGeneratorFactory {
			public function createURLGenerator( Payment $payment ): NullGenerator {
				return new NullGenerator();
			}
		};
	}

	private function makeFailingIbanUseCase(): ValidateIbanUseCase {
		return new ValidateIbanUseCase( new IbanBlockList( [] ), $this->makeFailingBankDataGenerator() );
	}

	public function withIdGenerator( PaymentIdRepository $idGenerator ): self {
		$this->idGenerator = $idGenerator;
		return $this;
	}

	public function withPaymentReferenceGenerator( PaymentReferenceCodeGenerator $generator ): self {
		$this->paymentReferenceCodeGenerator = $generator;
		return $this;
	}

	public function withPaymentRepositorySpy(): self {
		$this->repository = new PaymentRepositorySpy( [] );
		return $this;
	}

	public function getPaymentRepository(): PaymentRepository {
		return $this->repository;
	}

	public function withSucceedingIbanValidationUseCase(): self {
		$this->validateIbanUseCase = new ValidateIbanUseCase(
			new IbanBlockList( [] ),
			new KontoCheckBankDataGenerator( new SucceedingIbanValidator() )
		);
		return $this;
	}

	public function withFailingIbanValidationUseCase(): self {
		$this->validateIbanUseCase = $this->makeFailingIbanUseCase();
		return $this;
	}

	public function withUrlGeneratorFactory( UrlGeneratorFactory $factory ): self {
		$this->urlGeneratorFactory = $factory;
		return $this;
	}

	private function makePaymentValidator(): PaymentValidator {
		return new PaymentValidator();
	}

	private function makeFailingBankDataGenerator(): BankDataGenerator {
		return new class implements BankDataGenerator {
			public function getBankDataFromAccountData( string $account, string $bankCode ): ExtendedBankData {
				throw new \DomainException( 'getBankDataFromAccountData should not be called' );
			}

			public function getBankDataFromIban( Iban $iban ): ExtendedBankData {
				throw new \InvalidArgumentException( 'Invalid IBAN (for testing)' );
			}

		};
	}

	private function makePaymentProviderAdapterFactory(): PaymentProviderAdapterFactory {
		return new class implements PaymentProviderAdapterFactory {
			public function createProvider( Payment $payment ): PaymentProviderAdapter {
				return new DefaultPaymentProviderAdapter();
			}
		};
	}

	public function withPaymentProviderAdapter( PaymentProviderAdapter $paymentProviderAdapter ): self {
		$this->paymentProviderAdapterFactory = new class( $paymentProviderAdapter ) implements PaymentProviderAdapterFactory {
			public function __construct( private readonly PaymentProviderAdapter $paymentProviderAdapter ) {
			}

			public function createProvider( Payment $payment ): PaymentProviderAdapter {
				return $this->paymentProviderAdapter;
			}
		};
		return $this;
	}

}
