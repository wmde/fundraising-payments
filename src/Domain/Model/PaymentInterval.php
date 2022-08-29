<?php
// phpcs:ignoreFile -- Until phpcs has 8.1 enum support, see https://github.com/squizlabs/PHP_CodeSniffer/issues/3479
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

enum PaymentInterval: int
{
	case OneTime = 0;
	case Monthly = 1;
	case Quarterly = 3;
	case HalfYearly = 6;
	case Yearly = 12;
}
