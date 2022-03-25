<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Domain\TransferCodeGenerator;
use WMDE\Fundraising\PaymentContext\Tests\Data\DirectDebitBankData;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\ValidateIban\ValidateIbanUseCase;
use WMDE\FunValidators\ValidationResult;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase
 */
class CreatePaymentUseCaseTest extends TestCase {
	private const PAYMENT_ID = 2;

	public function testCreateCreditCardPayment(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeFixedIdGenerator(),
			$this->makePaymentRepository( new CreditCardPayment( self::PAYMENT_ID, Euro::newFromCents( 100 ), PaymentInterval::OneTime ) ),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
	}

	public function testCreatePayPalPayment(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeFixedIdGenerator(),
			$this->makePaymentRepository(
				new PayPalPayment( self::PAYMENT_ID, Euro::newFromCents( 100 ), PaymentInterval::OneTime )
			),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'PPL'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
	}

	public function testCreateSofortPayment(): void {
		$idGenerator = $this->makeFixedIdGenerator();
		$repo = $this->makePaymentRepository(
			new SofortPayment(
				self::PAYMENT_ID,
				Euro::newFromCents( 100 ),
				PaymentInterval::OneTime,
				'IamBankTransferCode42'
			)
		);
		$transferCodeGenerator = $this->createMock( TransferCodeGenerator::class );
		$transferCodeGenerator->expects( $this->once() )
			->method( 'generateTransferCode' )
			->with( 'TestPrefix' )
			->willReturn( 'IamBankTransferCode42' );
		$useCase = new CreatePaymentUseCase( $idGenerator, $repo, $transferCodeGenerator, $this->makeValidateIbanUseCase() );

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 0,
			paymentType: 'SUB',
			transferCodePrefix: 'TestPrefix'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
	}

	public function testCreateBankTransferPayment(): void {
		$idGenerator = $this->makeFixedIdGenerator();
		$repo = $this->makePaymentRepository(
			new BankTransferPayment(
				self::PAYMENT_ID,
				Euro::newFromCents( 400 ),
				PaymentInterval::Quarterly,
				'IamBankTransferCode23'
			)
		);
		$transferCodeGenerator = $this->createMock( TransferCodeGenerator::class );
		$transferCodeGenerator->expects( $this->once() )
			->method( 'generateTransferCode' )
			->with( 'TestPrefix' )
			->willReturn( 'IamBankTransferCode23' );
		$useCase = new CreatePaymentUseCase( $idGenerator, $repo, $transferCodeGenerator, $this->makeValidateIbanUseCase() );

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'UEB',
			transferCodePrefix: 'TestPrefix'
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
	}

	public function testCreateSofortPaymentFailsOnUnsupportedInterval(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeIdGeneratorStub(),
			$this->makeRepositoryStub(),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: PaymentInterval::Monthly->value,
			paymentType: 'SUB',
			transferCodePrefix: 'TestPrefix'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidIntervalFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeIdGeneratorStub(),
			$this->makeRepositoryStub(),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 100,
			interval: 1000,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidAmountFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeIdGeneratorStub(),
			$this->makeRepositoryStub(),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: -500,
			interval: 0,
			paymentType: 'MCP'
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreatePaymentWithInvalidPaymentTypeFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeIdGeneratorStub(),
			$this->makeRepositoryStub(),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 500,
			interval: 0,
			paymentType: 'TRA$HCOIN',
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
	}

	public function testCreateDirectDebitPayment(): void {
		$repo = $this->makePaymentRepository(
			DirectDebitPayment::create(
				self::PAYMENT_ID,
				Euro::newFromCents( 400 ),
				PaymentInterval::Quarterly,
				new Iban( DirectDebitBankData::IBAN ),
				DirectDebitBankData::BIC
			)
		);
		$useCase = new CreatePaymentUseCase(
			$this->makeIdGeneratorStub(),
			$repo,
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase()
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'BEZ',
			iban: DirectDebitBankData::IBAN,
			bic: DirectDebitBankData::BIC
		) );

		$this->assertInstanceOf( SuccessResponse::class, $result );
		$this->assertSame( self::PAYMENT_ID, $result->paymentId );
	}

	public function testCreateDirectDebitPaymentWithInvalidIbanFails(): void {
		$useCase = new CreatePaymentUseCase(
			$this->makeIdGeneratorStub(),
			$this->makeRepositoryStub(),
			$this->makeTransferCodeGeneratorStub(),
			$this->makeValidateIbanUseCase( blockList: [ DirectDebitBankData::IBAN ] )
		);

		$result = $useCase->createPayment( new PaymentCreationRequest(
			amountInEuroCents: 400,
			interval: 3,
			paymentType: 'BEZ',
			iban: DirectDebitBankData::IBAN,
			bic: DirectDebitBankData::BIC
		) );

		$this->assertInstanceOf( FailureResponse::class, $result );
		$this->assertEquals( "An invalid Iban was provided", $result->errorMessage );
	}

	private function makeIdGeneratorStub(): PaymentIDRepository {
		$idGenerator = $this->createMock( PaymentIDRepository::class );
		$idGenerator->expects( $this->never() )->method( 'getNewID' );
		return $idGenerator;
	}

	private function makeRepositoryStub(): PaymentRepository {
		$repo = $this->createMock( PaymentRepository::class );
		$repo->expects( $this->never() )->method( 'storePayment' );
		return $repo;
	}

	private function makeTransferCodeGeneratorStub(): TransferCodeGenerator {
		$generator = $this->createMock( TransferCodeGenerator::class );
		$generator->expects( $this->never() )->method( 'generateTransferCode' );
		return $generator;
	}

	private function makeFixedIdGenerator(): PaymentIDRepository {
		$idGenerator = $this->createMock( PaymentIDRepository::class );
		$idGenerator->method( 'getNewID' )->willReturn( self::PAYMENT_ID );
		return $idGenerator;
	}

	private function makePaymentRepository( Payment $expectedPayment ): PaymentRepository {
		$repo = $this->createMock( PaymentRepository::class );
		$repo->expects( $this->once() )->method( 'storePayment' )->with( $expectedPayment );
		return $repo;
	}

	/**
	 * @param array<string> $blockList
	 *
	 * @return ValidateIbanUseCase
	 */
	private function makeValidateIbanUseCase( array $blockList = [] ): ValidateIbanUseCase {
		$validator = $this->createMock( IbanValidator::class );
		$validator->method( 'validate' )->willReturn( new ValidationResult() );

		return new ValidateIbanUseCase( $validator, new IbanBlockList( $blockList ) );
	}
}
