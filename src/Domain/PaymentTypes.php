<?php
// phpcs:ignoreFile -- Until phpcs has 8.1 enum support, see https://github.com/squizlabs/PHP_CodeSniffer/issues/3479
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

enum PaymentTypes: string
{
	case DirectDebit = 'BEZ';
	case BankTransfer = 'UEB';
	case CreditCard = 'MCP';
	case Paypal = 'PPL';
	case Sofort = 'SUB';
}
