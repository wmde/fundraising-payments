<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;

/**
 * A deterministic implementation to test the iteration and factory method in the abstract class
 */
class DeterministicPaymentReferenceGenerator extends PaymentReferenceCodeGenerator {
	/**
	 * @var array<int,int>
	 */
	private array $sequenceMap;

	private int $charIndex;

	/**
	 * @param string $characterSequence The sequence, in which the characters should occur. Must only contain allowed characters from PaymentReferenceCode
	 */
	public function __construct( string $characterSequence ) {
		parent::__construct();
		$this->sequenceMap = array_map( fn( $char ) => intval( array_search( $char, $this->characters ) ), str_split( $characterSequence ) );
		$this->charIndex = 0;
	}

	protected function getNextCharacterIndex(): int {
		$index = $this->charIndex++;
		return $this->sequenceMap[$index];
	}

}
