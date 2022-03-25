<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode as WMDEReferenceCode;

class PaymentReferenceCode extends Type {

	public function getSQLDeclaration( array $column, AbstractPlatform $platform ): string {
		return 'VARCHAR(16)';
	}

	public function getName(): string {
		return 'PaymentReferenceCode';
	}

	public function convertToPHPValue( mixed $value, AbstractPlatform $platform ): ?WMDEReferenceCode {
		return WMDEReferenceCode::newFromString( strval( $value ) );
	}

	public function convertToDatabaseValue( mixed $value, AbstractPlatform $platform ): string {
		if ( !$value instanceof WMDEReferenceCode ) {
			throw new \InvalidArgumentException( 'Provided value must of the type ' . WMDEReferenceCode::class );
		}

		return $value->getFormattedCode();
	}
}
