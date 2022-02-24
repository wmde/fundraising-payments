<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use WMDE\Fundraising\PaymentContext\PaymentContextFactory;

class TestPaymentContextFactory {

	private Configuration $doctrineConfig;
	private PaymentContextFactory $contextFactory;
	private ?EntityManager $entityManager;
	private ?Connection $connection;

	/**
	 * @param array{db:array<string,mixed>} $config
	 */
	public function __construct( private array $config ) {
		$this->doctrineConfig = Setup::createConfiguration( true );
		$this->contextFactory = new PaymentContextFactory();
		$this->connection = null;
		$this->entityManager = null;
	}

	public function getConnection(): Connection {
		if ( $this->connection === null ) {
			$this->connection = $this->newConnection();
		}
		return $this->connection;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$this->entityManager = $this->newEntityManager();
		}
		return $this->entityManager;
	}

	private function newEntityManager(): EntityManager {
		$this->doctrineConfig->setMetadataDriverImpl( $this->contextFactory->newMappingDriver() );
		return EntityManager::create( $this->getConnection(), $this->doctrineConfig );
	}

	public function newSchemaCreator(): SchemaCreator {
		return new SchemaCreator( $this->newEntityManager() );
	}

	private function newConnection(): Connection {
		$connection = DriverManager::getConnection( $this->config['db'] );
		$this->contextFactory->registerDoctrineEuroType( $connection );
		$this->contextFactory->registerDoctrinePaymentIntervalType( $connection );
		return $connection;
	}
}
