<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20250916164428 extends AbstractMigration {
	private const string TABLE_NAME = 'payment_fee_change';

	public function getDescription(): string {
		return 'Create FeeChange payment table';
	}

	public function up( Schema $schema ): void {
		$feeChange = $schema->createTable( self::TABLE_NAME );
		$feeChange->addColumn( 'id', Types::INTEGER );
		$feeChange->addPrimaryKeyConstraint( PrimaryKeyConstraint::editor()
			->setUnquotedColumnNames( 'id' )
			->create()
		);
		$feeChange->addForeignKeyConstraint(
			'payment',
			[ 'id' ],
			[ 'id' ],
			[ 'onDelete' => 'CASCADE' ],
			'FK_A072317DBF396750'
		);
	}

	public function down( Schema $schema ): void {
		$schema->dropTable( self::TABLE_NAME );
	}
}
