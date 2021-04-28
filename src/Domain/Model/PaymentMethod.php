<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;

/**
 * @license GPL-2.0-or-later
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

	public function hasExternalProvider(): bool;

	public function paymentCompleted(): bool;

	public function getValuationDate(): ?DateTimeImmutable;

}
