<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\PaymentDataValidator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentDataValidator
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PaymentDataValidatorTest extends \PHPUnit\Framework\TestCase {

	private const MIN_DONATION_AMOUNT = 1;
	private const MAX_DONATION_AMOUNT = 100000;

	public function testGivenAmountWithinLimits_validationSucceeds(): void {
		$validator = $this->newPaymentValidator();
		$this->assertTrue( $validator->validate( 50, 'UEB' )->isSuccessful() );
	}

	public function testGivenAmountTooLow_validationFails(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( 0.2, 'UEB' )->isSuccessful() );
	}

	public function testGivenAmountTooHigh_validationFails(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( 100000, 'UEB' )->isSuccessful() );
	}

	public function testGivenAmountIsNotANumber_validationFails(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( 'much money', 'UEB' )->isSuccessful() );
	}

	public function testNumberEqualToBoundIsAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertTrue( $validator->validate( 1, 'UEB' )->isSuccessful() );
	}

	public function testStringNotationBelowLowerBoundIsNotAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( '0.1', 'UEB' )->isSuccessful() );
	}

	public function testStringNotationAboveLowerBoundIsAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertTrue( $validator->validate( '1.1', 'UEB' )->isSuccessful() );
	}

	public function testNumberEqualToUpperBoundIsNotAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( 100000, 'UEB' )->isSuccessful() );
	}

	public function testStringNotationAboveUpperBoundIsNotAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( '123456.78', 'UEB' )->isSuccessful() );
	}

	public function testStringNotationBelowUpperBoundIsAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertTrue( $validator->validate( '99999.99', 'UEB' )->isSuccessful() );
	}

	public function testBinaryNotationIsNotAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( '0b10100111001', 'UEB' )->isSuccessful() );
	}

	public function testUnknownPaymentMethodsAreNotAllowed(): void {
		$validator = $this->newPaymentValidator();
		$this->assertFalse( $validator->validate( 99, 'DOGE' )->isSuccessful() );
	}

	private function newPaymentValidator(): PaymentDataValidator {
		return new PaymentDataValidator(
			self::MIN_DONATION_AMOUNT,
			self::MAX_DONATION_AMOUNT,
			[ 'UEB', 'BEZ', 'PPL' ]
		);
	}

	public function testGivenEuroAmountWithinLimits_validationSucceeds(): void {
		$this->assertTrue(
			$this->newPaymentValidator()->validate(
				Euro::newFromInt( 50 ),
				'UEB'
			)->isSuccessful()
		);
	}

}
