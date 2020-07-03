<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use WMDE\FunValidators\ValidationResult;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class PaymentDataValidationResult extends ValidationResult {

	public const VIOLATION_AMOUNT_NOT_NUMERIC = 'Amount is not numeric';
	public const VIOLATION_AMOUNT_TOO_LOW = 'Amount too low';
	public const VIOLATION_AMOUNT_TOO_HIGH = 'Amount too high';
	public const VIOLATION_UNKNOWN_PAYMENT_TYPE = 'Unknown payment type';

	public const SOURCE_AMOUNT = 'amount';
	public const SOURCE_PAYMENT_TYPE = 'paymentType';

}
