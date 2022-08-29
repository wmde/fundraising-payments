<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use WMDE\Euro\Euro as WMDEEuro;

class Euro extends Type {

	public function getSQLDeclaration( array $column, AbstractPlatform $platform ): string {
		return 'INT';
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function getName(): string {
		return 'Euro';
	}

	public function convertToPHPValue( mixed $value, AbstractPlatform $platform ): WMDEEuro {
		return WMDEEuro::newFromCents( intval( $value ) );
	}

	public function convertToDatabaseValue( mixed $value, AbstractPlatform $platform ): int {
		if ( !$value instanceof WMDEEuro ) {
			throw new \InvalidArgumentException( 'Provided value must of the type WMDE\Euro\Euro' );
		}

		return $value->getEuroCents();
	}
}
