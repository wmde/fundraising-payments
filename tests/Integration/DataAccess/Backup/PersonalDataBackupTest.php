<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess\Backup;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\Backup\PersonalDataBackup;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

#[CoversClass( PersonalDataBackup::class )]
class PersonalDataBackupTest extends TestCase {
	private EntityManager $entityManager;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
	}

	public function testReturnsConfigurationForMultipleTables(): void {
		// A dummy condition because it needs to be a
		$condition = 'SELECT id FROM payment';
		$backup = new PersonalDataBackup( $this->entityManager );

		$configuration = $backup->getTableBackupConfigurationForContext( $condition );

		$this->assertCount( 1, $configuration );
		$paymentTableConfig = $configuration[0];
		$this->assertStringContainsString( $condition, $paymentTableConfig->conditions );
		$this->assertNotSame( $condition, $paymentTableConfig->conditions, 'Condition is supposed to be used in a subselect' );
		$tables = explode( ' ', $paymentTableConfig->tableName );
		$this->assertGreaterThan( 1, $tables, 'Configuration should encompass more than one payment type' );
	}

	#[DataProvider( 'provideInvalidSelectSQL' )]
	public function testFailsWhenConditionIsNotASelectQuery( string $condition ): void {
		$backup = new PersonalDataBackup( $this->entityManager );

		$this->expectException( \LogicException::class );

		$backup->getTableBackupConfigurationForContext( $condition );
	}

	/**
	 * @return iterable<array{string}>
	 */
	public static function provideInvalidSelectSQL(): iterable {
		yield 'empty string' => [ '' ];
		yield 'invalid SQL (no SELECT)' => [ 'id=5' ];
		yield 'invalid SQL (no FROM)' => [ 'SELECT id' ];

		// The code does not do further inspection and does not parse the whole SQL query.
	}

}
