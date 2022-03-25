<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * The payment reference code is a code that users put in a payment description in their banking app/form.
 *
 * It helps the accounting department to associate incoming payments with promised payment.
 *
 * The payment reference code is a balanced tradeoff between conflicting requirements:
 * - it must be short
 * - it must be random and unique
 * - some people are writing this by hand, on paper, so the character set must be unambiguous
 *
 * The smaller the character set gets, the longer the resulting code will be.
 *
 */
class PaymentReferenceCode {
	public const ALLOWED_CHARACTERS = 'ACDEFKLMNPRTWXYZ349';
	public const LENGTH_PREFIX = 2;
	public const LENGTH_CODE = 6;
	public const LENGTH_CHECKSUM = 1;
	public const READABILITY_DELIMITER = '-';

	/**
	 * @param string $prefix The accounting department uses the prefix to identify different types of payments without needing a lookup for each payment
	 * @param string $code A unique code, using the allowed character set
	 * @param string $checksum A Checksum character, derived from the concatenated prefix and code, with the number converted to the allowed characters set (@see ChecksumGenerator)
	 */
	public function __construct(
		private string $prefix,
		private string $code,
		private string $checksum
	) {
		$this->validate( $prefix, 'prefix', self::LENGTH_PREFIX );
		$this->validate( $code, 'code', self::LENGTH_CODE );
		$this->validate( $checksum, 'checksum', self::LENGTH_CHECKSUM );
	}

	private function validate( string $value, string $paramName, int $expectedLength ): void {
		$pattern = '/^[' . self::ALLOWED_CHARACTERS . ']{' . $expectedLength . '}$/';
		if ( !preg_match( $pattern, $value ) ) {
			throw new \UnexpectedValueException( sprintf(
				'Unexpected %s: "%s". It must be %d characters long and only contain "%s"',
				$paramName,
				$value,
				$expectedLength,
				self::ALLOWED_CHARACTERS
			) );
		}
	}

	public function __toString(): string {
		return $this->getFormattedCode();
	}

	public function getFormattedCode(): string {
		return implode( self::READABILITY_DELIMITER, [ $this->prefix, ...str_split( $this->code, self::LENGTH_CODE / 2 ), $this->checksum ] );
	}
}
