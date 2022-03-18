<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases;

enum ResponseStatus {
	case Success;
	case Failure;
}
