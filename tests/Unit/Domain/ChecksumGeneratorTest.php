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

		$this->assertSame( 'b', $generator->createChecksum( 'aaaa' ) );
		$this->assertSame( 'a', $generator->createChecksum( 'aaaaa' ) );
	}

	public function testCanGenerateChecksumWithManyCharacters(): void {
		$generator = new ChecksumGenerator( str_split( 'ACDEFKLMNPRSTWXYZ349' ) );

		$this->assertSame( 'W', $generator->createChecksum( 'AAAU' ) );
		$this->assertSame( '3', $generator->createChecksum( 'AAAA' ) );
		$this->assertSame( 'X', $generator->createChecksum( 'QAQA' ) );
		$this->assertSame( '9', $generator->createChecksum( 'ABCD' ) );
	}

	public function testIgnoresDashesUnderscoresAndSpaces(): void {
		$generator = new ChecksumGenerator( str_split( 'ACDEFKLMNPRSTWXYZ349' ) );

		$checksum = $generator->createChecksum( 'CAT' );

		$this->assertSame( $checksum, $generator->createChecksum( 'C-AT-' ) );
		$this->assertSame( $checksum, $generator->createChecksum( '_CAT_' ) );
		$this->assertSame( $checksum, $generator->createChecksum( 'C A T' ) );
	}

	public function testLowerAndMixedCaseProduceConsistentChecksum(): void {
		$generator = new ChecksumGenerator( str_split( 'ACDEFKLMNPRSTWXYZ349' ) );

		$this->assertSame( 'W', $generator->createChecksum( 'AAAU' ) );
		$this->assertSame( 'W', $generator->createChecksum( 'aAAU' ) );

		$this->assertSame( '3', $generator->createChecksum( 'aAaa' ) );
		$this->assertSame( '3', $generator->createChecksum( 'aaaa' ) );

		$this->assertSame( 'X', $generator->createChecksum( 'QaQa' ) );
		$this->assertSame( 'X', $generator->createChecksum( 'qaqa' ) );

		$this->assertSame( 'C', $generator->createChecksum( 'xxxx' ) );
		$this->assertSame( 'C', $generator->createChecksum( 'XXXX' ) );
	}

	public function testChecksumIsOneOfTheExpectedCharacters(): void {
		$characters = [ 'A', 'B', 'C', 'D', 'E', 'F' ];
		$generator = new ChecksumGenerator( $characters );

		// $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0];

		foreach ( $this->getRandomStrings() as $string ) {
			// $distribution[$generator->createChecksum( $string )]++;
			$this->assertContains(
				$generator->createChecksum( $string ),
				$characters
			);
		}

		//var_dump($distribution);exit;
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
