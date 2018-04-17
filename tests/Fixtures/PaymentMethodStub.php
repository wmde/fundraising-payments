<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;

class PaymentMethodStub implements PaymentMethod {

	public function getId(): string {
		return 'TST';
	}

	public function dispatch( callable $callback ): void {
	}
}