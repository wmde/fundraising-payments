<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\PaymentContext\Domain;

use WMDE\Euro\Euro;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PaymentDataValidator {

	private const VIOLATION_AMOUNT_NOT_NUMERIC = 'Amount is not numeric';
	private const VIOLATION_AMOUNT_TOO_LOW = 'Amount too low';
	private const VIOLATION_AMOUNT_TOO_HIGH = 'Amount too high';
	private const VIOLATION_UNKNOWN_PAYMENT_TYPE = 'Unknown payment type';

	private const SOURCE_AMOUNT = 'amount';
	private const SOURCE_PAYMENT_TYPE = 'paymentType';

	private $minAmount;
	private $maxAmount;
	private $allowedMethods = [];

	private $minAmountPerType;

	/**
	 * @param float $minAmount
	 * @param float $maxAmount
	 * @param array $allowedMethods
	 * @param float[] $minAmountPerType keys from the PaymentMethods enum
	 */
	public function __construct( float $minAmount, float $maxAmount, array $allowedMethods, array $minAmountPerType = [] ) {
		$this->minAmount = $minAmount;
		$this->maxAmount = $maxAmount;
		$this->allowedMethods = $allowedMethods;
		$this->minAmountPerType = $minAmountPerType;
	}

	/**
	 * @param mixed $amount For validation to succeed, needs to be numeric or Euro
	 * @param string $paymentMethodId
	 *
	 * @return ValidationResult
	 */
	public function validate( $amount, string $paymentMethodId ): ValidationResult {
		if ( !in_array( $paymentMethodId, $this->allowedMethods ) ) {
			return new ValidationResult(
				new ConstraintViolation(
					$paymentMethodId,
					self::VIOLATION_UNKNOWN_PAYMENT_TYPE,
					self::SOURCE_PAYMENT_TYPE
				)
			);
		}

		if ( $amount instanceof Euro ) {
			$amount = $amount->getEuroFloat();
		}

		if ( !is_numeric( $amount ) ) {
			return new ValidationResult(
				new ConstraintViolation(
					$amount,
					self::VIOLATION_AMOUNT_NOT_NUMERIC,
					self::SOURCE_AMOUNT
				)
			);
		}

		if ( $amount < $this->getMinAmountFor( $paymentMethodId ) ) {
			return new ValidationResult(
				new ConstraintViolation(
					$amount,
					self::VIOLATION_AMOUNT_TOO_LOW,
					self::SOURCE_AMOUNT
				)
			);
		}

		if ( $amount >= $this->maxAmount ) {
			return new ValidationResult(
				new ConstraintViolation(
					$amount,
					self::VIOLATION_AMOUNT_TOO_HIGH,
					self::SOURCE_AMOUNT
				)
			);
		}

		return new ValidationResult();
	}

	private function getMinAmountFor( string $paymentMethod ): float {
		if ( array_key_exists( $paymentMethod, $this->minAmountPerType ) ) {
			return $this->minAmountPerType[$paymentMethod];
		}

		return $this->minAmount;
	}
}
