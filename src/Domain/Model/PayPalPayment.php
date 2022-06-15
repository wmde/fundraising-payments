<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class PayPalPayment extends Payment implements BookablePayment {

	use LegacyBookingStatusTrait;

	private const PAYMENT_METHOD = 'PPL';

	private ?string $transactionId = null;

	/**
	 * @var array<string,string>
	 */
	private array $bookingData;

	private ?PayPalPayment $parentPayment = null;

	private ?DateTimeImmutable $valuationDate = null;

	public function __construct( int $id, Euro $amount, PaymentInterval $interval ) {
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );
		$this->bookingData = [];
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	public function isBooked(): bool {
		return $this->valuationDate !== null && !empty( $this->bookingData );
	}

	public function canBeBooked( array $transactionData ): bool {
		if ( !$this->isBooked() ) {
			return true;
		}
		// Booked "initial" payments (payments where "parentPayment" is null) can be booked as followup payments
		return $this->isBookedInitialPayment();
	}

	/**
	 * @param array<string,mixed> $transactionData Payment information from PayPal
	 * @param PaymentIDRepository $idGenerator Used for creating followup payments
	 * @return PayPalPayment
	 *
	 */
	public function bookPayment( array $transactionData, PaymentIDRepository $idGenerator ): PayPalPayment {
		$transformer = new PayPalBookingTransformer( $transactionData );
		if ( !$this->canBeBooked( $transactionData ) ) {
			throw new DomainException( 'Payment is already completed' );
		}

		if ( $this->isBookedInitialPayment() ) {
			return $this->createFollowUpPayment( $transactionData, $idGenerator );
		}

		$this->bookingData = $transformer->getBookingData();
		$this->valuationDate = $transformer->getValuationDate();
		$this->transactionId = $transformer->getTransactionId();
		return $this;
	}

	public function getTransactionId(): ?string {
		return $this->transactionId;
	}

	protected function getPaymentName(): string {
		return self::PAYMENT_METHOD;
	}

	protected function getPaymentSpecificLegacyData(): array {
		$legacyData = [];
		if ( $this->isBooked() ) {
			$legacyData = ( new PayPalBookingTransformer( $this->bookingData ) )->getLegacyData();
		}
		if ( $this->parentPayment !== null ) {
			$legacyData['parent_payment_id'] = $this->parentPayment->getId();
		}
		return $legacyData;
	}

	/**
	 * Create a booked followup payment
	 *
	 * @param array<string,mixed> $transactionData
	 * @param PaymentIDRepository $idGenerator
	 * @return PayPalPayment
	 */
	private function createFollowUpPayment( array $transactionData, PaymentIDRepository $idGenerator ): PayPalPayment {
		$followupPayment = new PayPalPayment( $idGenerator->getNewID(), $this->amount, $this->interval );
		$followupPayment->parentPayment = $this;
		return $followupPayment->bookPayment( $transactionData, $idGenerator );
	}

	private function isBookedInitialPayment(): bool {
		return $this->isBooked() && $this->parentPayment === null && $this->isRecurringPayment();
	}

	private function isRecurringPayment(): bool {
		return $this->interval !== PaymentInterval::OneTime;
	}

	public function getDisplayValues(): array {
		$parentValues = parent::getDisplayValues();
		$subtypeValues = $this->getPaymentSpecificLegacyData();
		return array_merge(
			$parentValues,
			$subtypeValues
		);
	}

	public function isCompleted(): bool {
		return $this->isBooked();
	}

	public function getParentPayment(): ?PayPalPayment {
		return $this->parentPayment;
	}
}
