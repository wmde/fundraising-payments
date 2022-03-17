<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment
 */
class BankTransferPaymentTest extends TestCase {

	public function testGivenEmptyBankTransferCodeThrowsException(): void {
		$this->expectException( \InvalidArgumentException::class );

		new BankTransferPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly, '' );
	}
}
