<?php
// phpcs:ignoreFile -- Until phpcs has 8.1 enum support, see https://github.com/squizlabs/PHP_CodeSniffer/issues/3479
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model\TransactionShapes;

enum CreditCardNotificationFields: string
{
	case TransactionId = 'transactionId';
	case Amount = 'amount';
	case CustomerId = 'customerId';
	case SessionId = 'sessionId';
	case AuthId = 'auth';
	case Title = 'title';
	case Country = 'country';
	case Currency = 'currency';

	public static function filterAllowedFields( array $fields ): array {
		// TODO iterate over self and return all found fields
		return [];
	}
}
