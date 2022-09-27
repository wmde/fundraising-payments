<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

interface CancellablePayment {

	public function isCancellable(): bool;

	public function isRestorable(): bool;

	public function cancel(): void;

	public function restore(): void;
}
