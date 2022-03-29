<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer;

/**
 * @implements AssociablePayment<PayPalPayment>
 */
class PayPalPayment extends Payment implements BookablePayment, AssociablePayment {

	private const PAYMENT_METHOD = 'PPL';

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

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	public function isCompleted(): bool {
		return $this->valuationDate !== null && !empty( $this->bookingData );
	}

	/**
	 * @param array<string,mixed> $transactionData
	 *
	 * @return void
	 *
	 * @throws DomainException
	 */
	public function bookPayment( array $transactionData ): void {
		$transformer = new PayPalBookingTransformer( $transactionData );
		if ( $this->isCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->bookingData = $transformer->getBookingData();
		$this->valuationDate = $transformer->getValuationDate();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getLegacyData(): array {
		return ( new PayPalBookingTransformer( $this->bookingData ) )->getLegacyData();
	}

	public function createFollowUpPayment( int $followUpPaymentId ): Payment {
		if ( $this->parentPayment !== null ) {
			throw new \RuntimeException( 'You can only create follow-up payments from initial, non-follow-ip payments' );
		}
		$payment = new PayPalPayment( $followUpPaymentId, $this->amount, $this->interval );
		$payment->parentPayment = $this;
		return $payment;
	}
}
