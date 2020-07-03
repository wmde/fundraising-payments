<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class SimpleTransferCodeGenerator implements TransferCodeGenerator {

	public function generateTransferCode( string $prefix ): string {
		$transferCode = $prefix;

		for ( $i = 0; $i < 6; ++$i ) {
			$transferCode .= $this->getRandomCharacter();
		}
		$transferCode .= '-' . $this->getRandomCharacter();

		return $transferCode;
	}

	private function getRandomCharacter(): string {
		$charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return $charSet[random_int( 0, strlen( $charSet ) - 1 )];
	}

}
