<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests;

class TestEnvironment {

	/**
	 * @var array{db:array<string,mixed>}
	 */
	private array $config;
	private TestPaymentContextFactory $factory;

	public static function newInstance(): self {
		$environment = new self(
			[
				'db' => [
					'driver' => 'pdo_mysql',
					'user' => 'fundraising',
					'password' => 'INSECURE PASSWORD',
					'dbname' => 'fundraising',
					'host' => 'database',
					'port' => 3306,
					'memory' => true,
				]
			]
		);

		$environment->install();

		return $environment;
	}

	/**
	 * @param array{db:array<string,mixed>} $config
	 */
	private function __construct( array $config ) {
		$this->config = $config;
		$this->factory = new TestPaymentContextFactory( $this->config );
	}

	private function install(): void {
		$schemaCreator = $this->getFactory()->newSchemaCreator();

		try {
			$schemaCreator->dropSchema();
		}
		catch ( \Exception $ex ) {
		}

		$schemaCreator->createSchema();
	}

	public function getFactory(): TestPaymentContextFactory {
		return $this->factory;
	}

}
