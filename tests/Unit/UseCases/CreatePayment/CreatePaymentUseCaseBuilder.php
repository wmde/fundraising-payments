<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\UrlGeneratorFactory;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FailingIbanValidator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FixedPaymentReferenceCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentRepositorySpy;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingIbanValidator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;

class CreatePaymentUseCaseBuilder {
	private PaymentIDRepository $idGenerator;
	private PaymentRepository $repository;
	private PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator;
	private UrlGeneratorFactory $urlGeneratorFactory;
	private ValidateIbanUseCase $validateIbanUseCase;
	private PaymentValidator $paymentValidator;

	public function __construct() {
		$this->idGenerator = $this->makeIdGeneratorStub();
		$this->repository = $this->makeRepositoryStub();
		$this->paymentReferenceCodeGenerator = $this->makePaymentReferenceGeneratorStub();
		$this->urlGeneratorFactory = $this->makePaymentURLFactoryStub();
		$this->validateIbanUseCase = $this->makeFailingIbanUseCase();
		$this->paymentValidator = $this->makePaymentValidator();
	}

	public function build(): CreatePaymentUseCase {
		return new CreatePaymentUseCase(
			$this->idGenerator,
			$this->repository,
			$this->paymentReferenceCodeGenerator,
			$this->paymentValidator,
			$this->validateIbanUseCase,
			$this->urlGeneratorFactory
		);
	}

	private function makeIdGeneratorStub(): PaymentIDRepository {
		return new class implements PaymentIDRepository {
			public function getNewID(): int {
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
		return new ValidateIbanUseCase( new FailingIbanValidator(), new IbanBlockList( [] ) );
	}

	public function withIdGenerator( PaymentIDRepository $idGenerator ): self {
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
		$this->validateIbanUseCase = new ValidateIbanUseCase( new SucceedingIbanValidator(), new IbanBlockList( [] ) );
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
}
