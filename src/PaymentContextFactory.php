<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class PaymentContextFactory {

	/**
	 * Use this constant for MappingDriverChain::addDriver
	 */
	public const ENTITY_NAMESPACE = 'WMDE\Fundraising\PaymentContext\Domain\Model';

	public const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping';

	/**
	 * @return MappingDriver
	 * @deprecated We no longer have a mix of mapping drivers.
	 *     Use {@see XmlDriver} with an array containing {@see PaymentContextFactory::DOCTRINE_CLASS_MAPPING_DIRECTORY}} instead.
	 */
	public function newMappingDriver(): MappingDriver {
		return new XmlDriver( self::DOCTRINE_CLASS_MAPPING_DIRECTORY );
	}

	public function registerCustomTypes( Connection $connection ): void {
		$this->registerDoctrineEuroType( $connection );
		$this->registerDoctrineIbanType( $connection );
		$this->registerDoctrinePaymentIntervalType( $connection );
	}

	public function registerDoctrineEuroType( Connection $connection ): void {
		static $isRegistered = false;
		if ( $isRegistered ) {
			return;
		}
		Type::addType( 'Euro', 'WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Euro' );
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping( 'Euro', 'Euro' );
		$isRegistered = true;
	}

	public function registerDoctrinePaymentIntervalType( Connection $connection ): void {
		static $isRegistered = false;
		if ( $isRegistered ) {
			return;
		}
		Type::addType( 'PaymentInterval', 'WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\PaymentInterval' );
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping( 'PaymentInterval', 'PaymentInterval' );
		$isRegistered = true;
	}

	public function registerDoctrineIbanType( Connection $connection ): void {
		static $isRegistered = false;
		if ( $isRegistered ) {
			return;
		}
		Type::addType( 'Iban', 'WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Iban' );
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping( 'Iban', 'Iban' );
		$isRegistered = true;
	}
}
