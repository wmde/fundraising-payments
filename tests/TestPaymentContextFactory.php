<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use WMDE\Fundraising\PaymentContext\PaymentContextFactory;

/**
 * @phpstan-import-type Params from DriverManager
 */
class TestPaymentContextFactory {

	private Configuration $doctrineConfig;
	private PaymentContextFactory $contextFactory;
	private ?EntityManager $entityManager;

	/**
	 * @param array{db:Params} $config
	 */
	public function __construct( private array $config ) {
		$this->contextFactory = new PaymentContextFactory();
		$this->doctrineConfig = ORMSetup::createXMLMetadataConfiguration(
			$this->contextFactory->getDoctrineMappingPaths(),
			true
		);
		$this->entityManager = null;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$this->entityManager = $this->newEntityManager();
		}
		return $this->entityManager;
	}

	public function newEntityManager(): EntityManager {
		return new EntityManager( $this->newConnection(), $this->doctrineConfig );
	}

	public function newSchemaCreator(): SchemaCreator {
		return new SchemaCreator( $this->newEntityManager() );
	}

	private function newConnection(): Connection {
		$connection = DriverManager::getConnection( $this->config['db'] );
		$this->contextFactory->registerCustomTypes( $connection );
		return $connection;
	}
}
