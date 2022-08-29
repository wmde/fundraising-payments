<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\CharacterIndexGenerator;

class IncrementalCharacterIndexGenerator implements CharacterIndexGenerator {

	private int $charIndex;

	public function __construct() {
		$this->charIndex = 0;
	}

	public function getNextCharacterIndex( int $max ): int {
		return $this->charIndex++;
	}
}
