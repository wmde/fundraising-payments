<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

interface CharacterIndexGenerator {

	public function getNextCharacterIndex( int $max ): int;
}
