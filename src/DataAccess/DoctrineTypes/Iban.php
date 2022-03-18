<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban as WMDEIban;

class Iban extends Type {

	public function getSQLDeclaration( array $column, AbstractPlatform $platform ): string {
		return 'VARCHAR(255)';
	}

	public function getName(): string {
		return 'Iban';
	}

	public function convertToPHPValue( mixed $value, AbstractPlatform $platform ): ?WMDEIban {
		if ( $value === null ) {
			return null;
		}

		return new WMDEIban( strval( $value ) );
	}

	public function convertToDatabaseValue( mixed $value, AbstractPlatform $platform ): ?string {
		if ( $value === null ) {
			return null;
		}

		if ( !$value instanceof WMDEIban ) {
			throw new \InvalidArgumentException( 'Provided value must of the type ' . WMDEIban::class );
		}

		return $value->toString();
	}
}
