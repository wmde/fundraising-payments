<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

interface CancellablePayment {

	public function isCancellable(): bool;

	public function cancel(): void;
}
