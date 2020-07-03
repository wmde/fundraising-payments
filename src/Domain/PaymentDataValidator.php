<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\Euro\Euro;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PaymentDataValidator {

	private $minAmount;
	private $maxAmount;
	private $allowedMethods = [];

	/**
	 * @param float $minAmount
	 * @param float $maxAmount
	 * @param array $allowedMethods
	 */
	public function __construct( float $minAmount, float $maxAmount, array $allowedMethods ) {
		$this->minAmount = $minAmount;
		$this->maxAmount = $maxAmount;
		$this->allowedMethods = $allowedMethods;
	}

	/**
	 * @param mixed $amount For validation to succeed, needs to be numeric or Euro
	 * @param string $paymentMethodId
	 *
	 * @return ValidationResult
	 */
	public function validate( $amount, string $paymentMethodId ): ValidationResult {
		$violations = array_filter( [
			$this->validatePaymentMethod( $paymentMethodId ),
			$this->validateAmount( $amount )
			]
		);

		return new ValidationResult( ...$violations );
	}

	private function validatePaymentMethod( string $paymentMethodId ): ?ConstraintViolation {
		if ( !in_array( $paymentMethodId, $this->allowedMethods ) ) {
			return new ConstraintViolation(
					$paymentMethodId,
					PaymentDataValidationResult::VIOLATION_UNKNOWN_PAYMENT_TYPE,
					PaymentDataValidationResult::SOURCE_PAYMENT_TYPE
				);
		}
		return null;
	}

	/**
	 * @param mixed $amount
	 *
	 * @return ConstraintViolation|null
	 */
	public function validateAmount( $amount ): ?ConstraintViolation {
		if ( $amount instanceof Euro ) {
			$amount = $amount->getEuroFloat();
		}

		if ( !is_numeric( $amount ) ) {
			return new ConstraintViolation(
					$amount,
					PaymentDataValidationResult::VIOLATION_AMOUNT_NOT_NUMERIC,
					PaymentDataValidationResult::SOURCE_AMOUNT
			);
		}

		if ( $amount < $this->minAmount ) {
			return new ConstraintViolation(
					$amount,
					PaymentDataValidationResult::VIOLATION_AMOUNT_TOO_LOW,
					PaymentDataValidationResult::SOURCE_AMOUNT
			);
		}

		if ( $amount >= $this->maxAmount ) {
			return new ConstraintViolation(
					$amount,
					PaymentDataValidationResult::VIOLATION_AMOUNT_TOO_HIGH,
					PaymentDataValidationResult::SOURCE_AMOUNT
			);
		}
		return null;
	}
}
