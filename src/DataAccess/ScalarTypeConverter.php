<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

/**
 * This class circumvents problems coming from Doctrine database results that by their very nature have to be
 * of type "mixed", which trips up static analysis tools when calling intval and strval
 * (because calling them with objects will generate a warning).
 *
 * @internal This should only be used inside the DataAccess namespace.
 */
class ScalarTypeConverter {
	public static function toInt( mixed $value ): int {
		return intval( self::assertScalarType( $value ) );
	}

	public static function toString( mixed $value ): string {
		return strval( self::assertScalarType( $value ) );
	}

	private static function assertScalarType( mixed $value ): int|string|bool|float {
		if ( is_scalar( $value ) ) {
			return $value;
		}
		throw new \InvalidArgumentException( "Given value is not a scalar type" );
	}
}
