<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class PayPalPayment extends Payment implements BookablePayment {

	private const PAYMENT_METHOD = 'PPL';

	/**
	 * @var array<string,string>
	 */
	private array $bookingData;

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

	public function paymentCompleted(): bool {
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
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->bookingData = $transformer->getBookingData();
		$this->valuationDate = $transformer->getValuationDate();
	}

	/**
	 * // TODO: What to do with child payments?
	 *
	 * @return array<string,mixed>
	 */
	public function getLegacyData(): array {
		return ( new PayPalBookingTransformer( $this->bookingData ) )->getLegacyData();
	}
}
