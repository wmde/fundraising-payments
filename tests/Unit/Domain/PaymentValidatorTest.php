<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResponse;

#[CoversClass( PaymentValidator::class )]
class PaymentValidatorTest extends TestCase {
	private const VALID_AMOUNT = 100;
	private const VALID_INTERVAL = 1;
	private const VALID_PAYMENT_TYPE = 'UEB';

	public function testAmountValidation(): void {
		$validator = new PaymentValidator();

		$result = $validator->validatePaymentData(
			-1,
			self::VALID_INTERVAL,
			self::VALID_PAYMENT_TYPE,
			$this->makeSucceedingDomainSpecificValidator()
		);

		$this->assertFalse( $result->isSuccessful() );
		$errors = $result->getValidationErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( PaymentValidator::SOURCE_AMOUNT, $errors[0]->getSource() );
	}

	public function testIntervalValidation(): void {
		$validator = new PaymentValidator();

		$result = $validator->validatePaymentData(
			self::VALID_AMOUNT,
			99,
			self::VALID_PAYMENT_TYPE,
			$this->makeSucceedingDomainSpecificValidator()
		);

		$this->assertFalse( $result->isSuccessful() );
		$errors = $result->getValidationErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( PaymentValidator::SOURCE_INTERVAL, $errors[0]->getSource() );
	}

	public function testPaymentTypeValidation(): void {
		$validator = new PaymentValidator();

		$result = $validator->validatePaymentData(
			self::VALID_AMOUNT,
			self::VALID_INTERVAL,
			'TRA$HCOIN',
			$this->makeSucceedingDomainSpecificValidator()
		);

		$this->assertFalse( $result->isSuccessful() );
		$errors = $result->getValidationErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( PaymentValidator::SOURCE_PAYMENT_TYPE, $errors[0]->getSource() );
	}

	public function testRecurringPaymentsNotAllowedForSofort(): void {
		$validator = new PaymentValidator();

		$result = $validator->validatePaymentData(
			self::VALID_AMOUNT,
			self::VALID_INTERVAL,
			PaymentType::Sofort->value,
			$this->makeSucceedingDomainSpecificValidator()
		);

		$this->assertFalse( $result->isSuccessful() );
		$errors = $result->getValidationErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( PaymentValidator::SOURCE_INTERVAL, $errors[0]->getSource() );
	}

	public function testInvalidIntervalWithSofortPaymentDoesNotLeadToErrorForPaymentType(): void {
		$validator = new PaymentValidator();

		$result = $validator->validatePaymentData(
			self::VALID_AMOUNT,
			99,
			PaymentType::Sofort->value,
			$this->makeSucceedingDomainSpecificValidator()
		);

		$this->assertFalse( $result->isSuccessful() );
		$errors = $result->getValidationErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( PaymentValidator::SOURCE_INTERVAL, $errors[0]->getSource() );
	}

	public function testDomainValidation(): void {
		$validator = new PaymentValidator();

		$result = $validator->validatePaymentData(
			self::VALID_AMOUNT,
			self::VALID_INTERVAL,
			self::VALID_PAYMENT_TYPE,
			$this->makeFailingDomainSpecificValidator()
		);

		$this->assertFalse( $result->isSuccessful() );
		$errors = $result->getValidationErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( 'domain_specific_check_1', $errors[0]->getSource() );
		$this->assertSame( 'Amount is bad omen in China', $errors[0]->getMessageIdentifier() );
	}

	private function makeSucceedingDomainSpecificValidator(): DomainSpecificPaymentValidator {
		$validator = $this->createStub( DomainSpecificPaymentValidator::class );
		$validator->method( 'validatePaymentData' )->willReturn( ValidationResponse::newSuccessResponse() );
		return $validator;
	}

	private function makeFailingDomainSpecificValidator(): DomainSpecificPaymentValidator {
		$validator = $this->createStub( DomainSpecificPaymentValidator::class );
		$validator->method( 'validatePaymentData' )->willReturn( ValidationResponse::newFailureResponse( [
			new ConstraintViolation( 8, 'Amount is bad omen in China', 'domain_specific_check_1' )
		] ) );
		return $validator;
	}
}
