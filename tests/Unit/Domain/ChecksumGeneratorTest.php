<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\PaymentContext\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\Frontend\PaymentContext\Domain\ChecksumGenerator;

/**
 * @covers \WMDE\Fundraising\Frontend\PaymentContext\Domain\ChecksumGenerator
 *
 * @licence GNU GPL v2+
 */
class ChecksumGeneratorTest extends TestCase {

	public function testCannotConstructWithLessThanTwoCharacters(): void {
		$this->expectException( \InvalidArgumentException::class );
		new ChecksumGenerator( [ 'a' ] );
	}

	public function testCanGenerateChecksumWithTwoCharacters(): void {
		$generator = new ChecksumGenerator( [ 'a', 'b' ] );

		$this->assertSame( 'a', $generator->createChecksum( 'aaaa' ) );
		$this->assertSame( 'b', $generator->createChecksum( 'aaaaa' ) );
	}

	public function testCanGenerateChecksumWithManyCharacters(): void {
		$generator = new ChecksumGenerator( str_split( 'ACDEFKLMNPRSTWXYZ349' ) );

		$this->assertSame( 'W', $generator->createChecksum( 'AAAU' ) );
		$this->assertSame( '3', $generator->createChecksum( 'AAAA' ) );
		$this->assertSame( 'X', $generator->createChecksum( 'QAQA' ) );
		$this->assertSame( '9', $generator->createChecksum( 'ABCD' ) );
	}

	public function testChecksumIsOneOfTheExpectedCharacters(): void {
		$characters = [ 'A', 'B', 'C', 'D', 'E', 'F' ];
		$generator = new ChecksumGenerator( $characters );

		foreach ( $this->getRandomStrings() as $string ) {
			$this->assertContains(
				$generator->createChecksum( $string ),
				$characters
			);
		}
	}

	public function getRandomStrings(): iterable {
		$characters = str_split( 'ACDEFKLMNPRSTWXYZ349-' );
		$characterCount = count( $characters );

		for ( $i = 0; $i < 1000; $i++ ) {
			yield implode(
				'',
				array_map(
					function() use ( $characters, $characterCount ) {
						return $characters[mt_rand( 0, $characterCount - 1 )];
					},
					array_fill( 0, 10, null )
				)
			);
		}
	}

}
