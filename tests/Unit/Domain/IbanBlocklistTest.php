<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @covers WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist
 */
class IbanBlocklistTest extends TestCase {

	private $iban;

	protected function setUp(): void {
		$this->iban = new Iban( 'DE33100205000001194700' );
	}

	public function testGivenEmptyBlocklist_ibanIsNotBlocked(): void {
		$blocklist = new IbanBlocklist( [] );

		$this->assertFalse( $blocklist->isIbanBlocked( $this->iban ) );
	}

	public function testGivenNonmatchingBlocklist_ibanIsNotBlocked(): void {
		$blocklist = new IbanBlocklist( [ 'DE56500700100000020123' ] );

		$this->assertFalse( $blocklist->isIbanBlocked( $this->iban ) );
	}

	public function testGivenMacthingBlocklist_ibanIsBlocked(): void {
		$blocklist = new IbanBlocklist( [ 'DE33100205000001194700' ] );

		$this->assertTrue( $blocklist->isIbanBlocked( $this->iban ) );
	}
}
