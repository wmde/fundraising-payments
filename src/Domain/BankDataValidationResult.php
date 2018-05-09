<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class BankDataValidationResult {
	public const SOURCE_IBAN = 'iban';
	public const SOURCE_BIC = 'bic';
	public const SOURCE_BANK_NAME = 'bank-name';
	public const SOURCE_BANK_CODE = 'bank-code';
	public const SOURCE_BANK_ACCOUNT = 'bank-account';

	public const VIOLATION_MISSING = 'missing';
	public const VIOLATION_WRONG_LENGTH = 'wrong-length';
	public const VIOLATION_INVALID_BIC = 'invalid_bic';
}