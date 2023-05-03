<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

enum PaymentType: string
{
	case DirectDebit = 'BEZ';
	case BankTransfer = 'UEB';
	case CreditCard = 'MCP';
	case Paypal = 'PPL';
	case Sofort = 'SUB';
}
