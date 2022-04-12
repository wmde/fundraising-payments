<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use WMDE\Euro\Euro;

abstract class Payment implements LegacyDataTransformer {
	protected int $id;
	protected Euro $amount;
	protected PaymentInterval $interval;
	protected string $paymentMethod;

	protected function __construct( int $id, Euro $amount, PaymentInterval $interval, string $paymentMethod ) {
		$this->id = $id;
		$this->amount = $amount;
		$this->interval = $interval;
		$this->paymentMethod = $paymentMethod;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getLegacyData(): LegacyPaymentData {
		return new LegacyPaymentData(
			$this->amount->getEuroCents(),
			$this->interval->value,
			$this->getPaymentName(),
			$this->getPaymentSpecificLegacyData()
		);
	}

	/**
	 * This is just for providing payment name for LegacyPaymentData
	 *
	 * @return string
	 */
	abstract protected function getPaymentName(): string;

	/**
	 * @return array<string,mixed>
	 */
	abstract protected function getPaymentSpecificLegacyData(): array;

	/**
	 * @return Euro
	 */
	public function getAmount(): Euro {
		return $this->amount;
	}

	/**
	 * @return PaymentInterval
	 */
	public function getInterval(): PaymentInterval {
		return $this->interval;
	}
}
