<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\CreditCardBookingTransformer;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCardPayment extends Payment implements BookablePayment {

	private const PAYMENT_METHOD = 'MCP';

	/**
	 * @var array<string,string>
	 */
	protected array $bookingData;

	protected ?DateTimeImmutable $valuationDate = null;

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
		return $this->valuationDate === null && empty( $this->bookingData );
	}

	public function bookPayment( array $transactionData, PaymentIdRepository $idGenerator ): Payment {
		$transformer = new CreditCardBookingTransformer( $transactionData );
		if ( $this->isBooked() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		if ( !$this->getAmount()->equals( $transformer->getAmount() ) ) {
			throw new \UnexpectedValueException( sprintf(
				'Payment amount in transaction data (%s) must match original payment amount (%s)',
				$transformer->getAmount()->getEuroString(),
				$this->getAmount()->getEuroString()
			) );
		}
		$this->bookingData = $transformer->getBookingData();
		$this->valuationDate = $transformer->getValuationDate();
		return $this;
	}

	protected function getPaymentName(): string {
		return self::PAYMENT_METHOD;
	}

	protected function getPaymentSpecificLegacyData(): array {
		if ( $this->isBooked() ) {
			return ( new CreditCardBookingTransformer( $this->bookingData ) )->getLegacyData();
		}
		return [];
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
}
