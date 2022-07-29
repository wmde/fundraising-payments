<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Migrations;

use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create payment tables and indexes for the payment domain.
 *
 * This migration is part of the "payment refactoring" epic
 * {@see https://phabricator.wikimedia.org/T192323} which moved
 * the persistence of payment-related data from the individual bounded contexts
 * (donations, memberships) into its own bounded context (payment).
 * This migration creates the persistence tables for the refactored
 * 'payment' bounded context.
 *
 * The genesis of this SQL was as follows:
 * - We created the domain models (in {@see \WMDE\Fundraising\PaymentContext\Domain\Model\ })
 * - We created the XML database mappings
 * - We used a the {@see \Doctrine\ORM\Tools\SchemaTool} class to generate the SQL statements from the mappings
 */
final class Version20220729162240 extends AbstractMigration {
	public function getDescription(): string {
		return 'Create the payment tables and indexes for the payment.';
	}

	public function up( Schema $schema ): void {
		$this->verifyDatabasePlatformIsMariaDB();

		$this->addSql( <<<'EOT'
CREATE TABLE payment_id (
    id INT AUTO_INCREMENT NOT NULL, 
    payment_id INT UNSIGNED DEFAULT 0 NOT NULL, 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);

		$this->addSql( <<<'EOT'
CREATE TABLE payment_reference_codes (
    code VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, 
    PRIMARY KEY(code)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);

		$this->addSql( <<<'EOT'
CREATE TABLE payments (
    id INT NOT NULL, 
    amount INT NOT NULL, 
    payment_interval INT NOT NULL, 
    payment_method VARCHAR(3) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);
		$this->addSql( <<<'EOT'
CREATE TABLE payments_bank_transfer (
    id INT NOT NULL, 
    payment_reference_code VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`,
    is_cancelled TINYINT(1) NOT NULL,
    
    UNIQUE INDEX UNIQ_F63CD417E5FE723C (payment_reference_code), 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);

		$this->addSql( <<<'EOT'
CREATE TABLE payments_credit_card (
	id INT NOT NULL,
	valuation_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
	booking_data LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT '(DC2Type:json)',
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);

		$this->addSql( <<<'EOT'
CREATE TABLE payments_direct_debit (
    id INT NOT NULL,
    iban VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`,
    bic VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`,
    is_cancelled TINYINT(1) NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);

		$this->addSql( <<<'EOT'
CREATE TABLE payments_paypal (
	id INT NOT NULL,
	parent_payment_id INT DEFAULT NULL,
	valuation_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
	booking_data LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT '(DC2Type:json)',
	transaction_id VARCHAR(36) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`,
	
	INDEX IDX_2A359C3C438027EB (parent_payment_id),
	INDEX ppl_transaction_id (transaction_id),
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);

		$this->addSql( <<<'EOT'
CREATE TABLE payments_sofort (
	id INT NOT NULL,
	payment_reference_code VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`,
	valuation_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
	transaction_id VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`,
	
	UNIQUE INDEX UNIQ_7E122AB3E5FE723C (payment_reference_code),
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB 
EOT
);
	}

	public function down( Schema $schema ): void {
		$this->verifyDatabasePlatformIsMariaDB();

		$this->addSql( 'DROP TABLE payment_id' );
		$this->addSql( 'DROP TABLE payment_reference_codes' );
		$this->addSql( 'DROP TABLE payments' );
		$this->addSql( 'DROP TABLE payments_bank_transfer' );
		$this->addSql( 'DROP TABLE payments_credit_card' );
		$this->addSql( 'DROP TABLE payments_direct_debit' );
		$this->addSql( 'DROP TABLE payments_paypal' );
		$this->addSql( 'DROP TABLE payments_sofort' );
	}

	private function verifyDatabasePlatformIsMariaDB(): void {
		$this->abortIf(
			!$this->connection->getDatabasePlatform() instanceof MariaDBPlatform,
			"Migration can only be executed safely on MariaDB platform (at least 10.4)."
		);
	}
}
