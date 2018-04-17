<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface PaymentMethod {

	public const BANK_TRANSFER = 'UEB';
	public const CREDIT_CARD = 'MCP';
	public const DIRECT_DEBIT = 'BEZ';
	public const PAYPAL = 'PPL';
	public const SOFORT = 'SUB';

	/**
	 * @return string Element of the PaymentMethod:: enum
	 */
	public function getId(): string;

	/**
	 * Execute code for a specific payment method
	 *
	 * @param callable $callback
	 * @return mixed
	 */
	public function dispatch( callable $callback );

}
