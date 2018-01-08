<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\PaymentContext\Domain\Model;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
final class PaymentMethods {

	public const BANK_TRANSFER = 'UEB';
	public const CREDIT_CARD = 'MCP';
	public const DIRECT_DEBIT = 'BEZ';
	public const PAYPAL = 'PPL';
	public const SOFORT = 'SUB';

	public static function getList(): array {
		return ( new \ReflectionClass( self::class ) )->getConstants();
	}

}
