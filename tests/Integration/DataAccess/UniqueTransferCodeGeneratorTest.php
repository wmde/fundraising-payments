<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Euro\Euro;
use WMDE\Fundraising\DonationContext\DataAccess\DoctrineDonationRepository;
use WMDE\Fundraising\DonationContext\Domain\Model\Donation;
use WMDE\Fundraising\DonationContext\Domain\Model\DonationPayment;
use WMDE\Fundraising\DonationContext\Tests\Data\ValidDonation;
use WMDE\Fundraising\DonationContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\DataAccess\UniqueTransferCodeGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\TransferCodeGenerator;

/**
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\UniqueTransferCodeGenerator
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class UniqueTransferCodeGeneratorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getFactory()->getEntityManager();
	}

	private function newUniqueGenerator(): TransferCodeGenerator {
		return new UniqueTransferCodeGenerator(
			$this->newFakeGenerator(),
			$this->entityManager
		);
	}

	private function newFakeGenerator(): TransferCodeGenerator {
		return new class() implements TransferCodeGenerator {
			private $position = 0;

			public function generateTransferCode( string $prefix ): string {
				return $prefix . [ 'first', 'second', 'third' ][$this->position++];
			}
		};
	}

	public function testWhenFirstResultIsUnique_itGetsReturned(): void {
		$this->assertSame( 'X-first', $this->newUniqueGenerator()->generateTransferCode( 'X-' ) );
	}

	public function testWhenFirstResultIsNotUnique_secondResultGetsReturned(): void {
		$this->storeDonationWithTransferCode( 'X-first' );
		$this->assertSame( 'X-second', $this->newUniqueGenerator()->generateTransferCode( 'X-' ) );
	}

	private function storeDonationWithTransferCode( string $code ): void {
		$donation = new Donation(
			null,
			Donation::STATUS_NEW,
			ValidDonation::newDonor(),
			new DonationPayment(
				Euro::newFromFloat( 13.37 ),
				3,
				new BankTransferPayment( $code )
			),
			Donation::OPTS_INTO_NEWSLETTER,
			ValidDonation::newTrackingInfo()
		);

		( new DoctrineDonationRepository( $this->entityManager ) )->storeDonation( $donation );
	}

	public function testWhenFirstAndSecondResultsAreNotUnique_thirdResultGetsReturned(): void {
		$this->storeDonationWithTransferCode( 'X-first' );
		$this->storeDonationWithTransferCode( 'X-second' );
		$this->assertSame( 'X-third', $this->newUniqueGenerator()->generateTransferCode( 'X-' ) );
	}

}