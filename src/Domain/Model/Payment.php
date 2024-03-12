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

	/**
	 * Donations and memberships database tables currently (2024-03-12) store payment data.
	 * Some code (in the Fundraising Operation Center ) still uses that legacy data instead of using the payment tables.
	 * The following tickets track the progress of removing the legacy data:
	 * https://phabricator.wikimedia.org/T320781 using new payments tables in FOC
	 * https://phabricator.wikimedia.org/T359941 removing PaymentSpecificLegacyData from donation and membership
	 * https://phabricator.wikimedia.org/T359950 removing amount, interval and payment name from donation and membership
	 *
	 * @return LegacyPaymentData
	 */
	public function getLegacyData(): LegacyPaymentData {
		return new LegacyPaymentData(
			$this->amount->getEuroCents(),
			$this->interval->value,
			$this->getPaymentName(),
			$this->getPaymentSpecificLegacyData()
		);
	}

	/**
	 * This is just for providing payment name for LegacyPaymentData.
	 * Will be the string value from the {@see PaymentType} enum
	 *
	 * @return string
	 */
	abstract protected function getPaymentName(): string;

	/**
	 * Data for the donation "data blob" and the deprecated bank data columns in memberships.
	 * See https://phabricator.wikimedia.org/T359941 for the current status of the removal.
	 *
	 * @return array<string,scalar>
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

	/**
	 * @return array<string,scalar> Array containing all relevant payment values to display in user frontends
	 */
	public function getDisplayValues(): array {
		return [
			"amount" => $this->getAmount()->getEuroCents(),
			"interval" => $this->getInterval()->value,
			"paymentType" => $this->getPaymentName()
		];
	}

	abstract public function isCompleted(): bool;
}
