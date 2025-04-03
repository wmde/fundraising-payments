<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests;

use Doctrine\DBAL\Tools\DsnParser;

/**
 * @phpstan-import-type Params from \Doctrine\DBAL\DriverManager
 */
class TestEnvironment {

	/**
	 * @var array{db:Params}
	 */
	private array $config;
	private TestPaymentContextFactory $factory;

	public static function newInstance(): self {
		$connectionParams = [
			'driver' => 'pdo_mysql',
			'user' => 'fundraising',
			'password' => 'INSECURE PASSWORD',
			'dbname' => 'fundraising',
			'host' => 'database',
			'port' => 3306,
			'memory' => true,
		];
		if ( isset( $_ENV['TEST_DB_DSN'] ) ) {
			$dsnParser = new DsnParser( [ 'mysql' => 'pdo_mysql' ] );
			/** @phpstan-ignore-next-line argument.type */
			$connectionParams = $dsnParser->parse( strval( $_ENV['TEST_DB_DSN'] ) );
		}
		$environment = new self( [ 'db' => $connectionParams ] );

		$environment->install();

		return $environment;
	}

	/**
	 * @param array{db:Params} $config
	 */
	private function __construct( array $config ) {
		$this->config = $config;
		$this->factory = new TestPaymentContextFactory( $this->config );
	}

	private function install(): void {
		$schemaCreator = $this->getFactory()->newSchemaCreator();

		try {
			$schemaCreator->dropSchema();
		} catch ( \Exception $ex ) {
			echo $ex->getMessage();
		}

		$schemaCreator->createSchema();
	}

	public function getFactory(): TestPaymentContextFactory {
		return $this->factory;
	}

}
