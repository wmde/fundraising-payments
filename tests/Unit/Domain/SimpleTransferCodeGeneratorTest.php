<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain;

use WMDE\Fundraising\PaymentContext\Domain\SimpleTransferCodeGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\SimpleTransferCodeGenerator
 *
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class SimpleTransferCodeGeneratorTest extends \PHPUnit\Framework\TestCase {

	public function testGenerateBankTransferCode_matchesRegex(): void {
		$generator = new SimpleTransferCodeGenerator();
		$this->assertRegExp( '/W-Q-[A-Z]{6}-[A-Z]/', $generator->generateTransferCode( 'W-Q-' ) );
	}

}
