<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
final class PaymentMethods {

	public static function getList(): array {
		return ( new \ReflectionClass( PaymentMethod::class ) )->getConstants();
	}

}
