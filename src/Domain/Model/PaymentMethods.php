<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
final class PaymentMethods {

	public static function getList(): array {
		return ( new \ReflectionClass( PaymentMethod::class ) )->getConstants();
	}

}
