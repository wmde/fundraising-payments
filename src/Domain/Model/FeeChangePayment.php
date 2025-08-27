<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use WMDE\Euro\Euro;

class FeeChangePayment extends Payment {
	/**
	 * This payment is meant for functionality where a donor or member wants to update their payment information without giving more payment details.
	 * It **must not** be used as the payment when creating donations or memberships!
	 */
	private const PAYMENT_METHOD = 'FCH';

	private function __construct( int $id, Euro $amount, PaymentInterval $interval ) {
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );
	}

	public static function create( int $id, Euro $amount, PaymentInterval $interval ): self {
		return new self( $id, $amount, $interval );
	}

	public function anonymise(): void {
	}

	protected function getPaymentName(): string {
		return self::PAYMENT_METHOD;
	}

	protected function getPaymentSpecificLegacyData(): array {
		return [];
	}

	public function isCompleted(): bool {
		return true;
	}
}
