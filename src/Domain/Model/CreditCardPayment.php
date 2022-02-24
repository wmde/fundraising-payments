<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\CreditCardBookingTransformer;

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

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	public function paymentCompleted(): bool {
		return $this->valuationDate !== null && !empty( $this->bookingData );
	}

	public function bookPayment( array $transactionData ): void {
		$transformer = new CreditCardBookingTransformer( $transactionData );
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->bookingData = $transformer->getBookingData();
		$this->valuationDate = $transformer->getValuationDate();
	}

	public function getLegacyData(): array {
		return ( new CreditCardBookingTransformer( $this->bookingData ) )->getLegacyData();
	}

}
