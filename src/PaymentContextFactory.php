<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

class PaymentContextFactory {

	private const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping';

	/**
	 * Return filesystem paths to Doctrine mapping files (.dcm.xml files)
	 *
	 * @return string[]
	 */
	public function getDoctrineMappingPaths(): array {
		return [ self::DOCTRINE_CLASS_MAPPING_DIRECTORY ];
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
