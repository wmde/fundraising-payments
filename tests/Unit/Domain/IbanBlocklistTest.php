<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlockList;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @covers WMDE\Fundraising\PaymentContext\Domain\IbanBlockList
 */
class IbanBlocklistTest extends TestCase {

	private const IBAN = 'DE33100205000001194700';

	public function testGivenEmptyBlockList_ibanIsNotBlocked(): void {
		$blocklist = new IbanBlockList( [] );

		$this->assertFalse( $blocklist->isIbanBlocked( self::IBAN ) );
	}

	public function testGivenNonMatchingBlockList_ibanIsNotBlocked(): void {
		$blocklist = new IbanBlockList( [ 'DE56500700100000020123' ] );

		$this->assertFalse( $blocklist->isIbanBlocked( self::IBAN ) );
	}

	public function testGivenMatchingBlockList_ibanIsBlocked(): void {
		$blocklist = new IbanBlockList( [ self::IBAN ] );

		$this->assertTrue( $blocklist->isIbanBlocked( self::IBAN ) );
	}
}
