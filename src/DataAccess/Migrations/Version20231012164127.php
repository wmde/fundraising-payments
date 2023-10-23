<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231012164127 extends AbstractMigration {
	public function getDescription(): string {
		return 'Add payment_paypal_identifier table and indexes';
	}

	public function up( Schema $schema ): void {
		$this->addSql( <<<'EOT'
CREATE TABLE payment_paypal_identifier (
    payment_id INT NOT NULL,
    identifier_type VARCHAR(1) NOT NULL,
    subscription_id VARCHAR(255) DEFAULT NULL,
    transaction_id VARCHAR(255) DEFAULT NULL,
    order_id VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(payment_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
EOT
		);
		$this->addSql( 'ALTER TABLE payment_paypal_identifier ADD CONSTRAINT FK_D7AFB034C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)' );
		$this->addSql( 'CREATE INDEX payment_paypal_identifier_transaction_id_index ON payment_paypal_identifier (transaction_id)' );
		$this->addSql( 'CREATE INDEX payment_paypal_identifier_order_id_index ON payment_paypal_identifier (order_id)' );
		$this->addSql( 'CREATE INDEX payment_paypal_identifier_subscription_id_index ON payment_paypal_identifier (subscription_id)' );
	}

	public function down( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE payment_paypal_identifier DROP FOREIGN KEY FK_D7AFB034C3A3BB' );
		$this->addSql( 'DROP TABLE payment_paypal_identifier' );
	}
}
