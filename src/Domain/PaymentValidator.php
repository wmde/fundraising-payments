<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResponse;

class PaymentValidator {
	public const SOURCE_AMOUNT = 'amount';
	public const SOURCE_INTERVAL = 'interval';
	public const SOURCE_PAYMENT_TYPE = 'paymentType';

	/**
	 * @var ConstraintViolation[]
	 */
	private array $errors = [];

	public function __construct(
		private DomainSpecificPaymentValidator $domainValidator
	) {
	}

	public function validatePaymentData( int $amount, int $interval, string $paymentType ): ValidationResponse {
		$this->errors = [];
		$this->validateAmount( $amount );
		$this->validateInterval( $interval );
		$this->validatePaymentType( $paymentType );

		if ( count( $this->errors ) > 0 ) {
			return ValidationResponse::newFailureResponse( $this->errors );
		}

		return $this->validateDomain( $amount, $interval );
	}

	private function validateAmount( int $amount ): void {
		try {
			Euro::newFromCents( $amount );
		} catch ( \InvalidArgumentException $e ) {
			$this->errors[] = new ConstraintViolation( $amount, $e->getMessage(), self::SOURCE_AMOUNT );
		}
	}

	private function validateInterval( int $interval ): void {
		if ( PaymentInterval::tryFrom( $interval ) === null ) {
			$this->errors[] = new ConstraintViolation( $interval, 'Invalid Interval', self::SOURCE_INTERVAL );
		}
	}

	private function validatePaymentType( string $paymentType ): void {
		if ( PaymentTypes::tryFrom( $paymentType ) === null ) {
			$this->errors[] = new ConstraintViolation( $paymentType, 'Unknown payment type', self::SOURCE_PAYMENT_TYPE );
		}
	}

	private function validateDomain( int $amount, int $interval ): ValidationResponse {
		return $this->domainValidator->validatePaymentData( Euro::newFromCents( $amount ), PaymentInterval::from( $interval ) );
	}
}
