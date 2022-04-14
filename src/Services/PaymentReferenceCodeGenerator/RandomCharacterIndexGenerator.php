<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator;

use WMDE\Fundraising\PaymentContext\Domain\CharacterIndexGenerator;

/**
 * @codeCoverageIgnore
 */
class RandomCharacterIndexGenerator implements CharacterIndexGenerator {

	public function getNextCharacterIndex( int $max ): int {
		return mt_rand( 0, $max );
	}
}
