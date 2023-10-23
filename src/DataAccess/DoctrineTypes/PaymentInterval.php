<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval as DomainPaymentInterval;
use WMDE\Fundraising\PaymentContext\ScalarTypeConverter;

class PaymentInterval extends Type {

	public function getSQLDeclaration( array $column, AbstractPlatform $platform ): string {
		return 'INT';
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function getName(): string {
		return 'PaymentInterval';
	}

	public function convertToPHPValue( mixed $value, AbstractPlatform $platform ): DomainPaymentInterval {
		return DomainPaymentInterval::from( ScalarTypeConverter::toInt( $value ) );
	}

	public function convertToDatabaseValue( mixed $value, AbstractPlatform $platform ): int {
		if ( !$value instanceof DomainPaymentInterval ) {
			throw new \InvalidArgumentException( 'Provided value must of the type ' . DomainPaymentInterval::class );
		}

		return $value->value;
	}
}
