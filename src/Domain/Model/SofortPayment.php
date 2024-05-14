<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class SofortPayment extends Payment implements BookablePayment {

	private const PAYMENT_METHOD = 'SUB';

	private const LEGACY_TO_DISPLAY_KEY_MAP = [
		'transaction_id' => 'transactionId',
		'valuation_date' => 'valuationDate',
		'ueb_code' => 'paymentReferenceCode'
	];

	/**
	 * This field is nullable to allow for anonymisation
	 *
	 * @var PaymentReferenceCode|null
	 */
	private ?PaymentReferenceCode $paymentReferenceCode;

	private ?string $transactionId = null;

	private ?DateTimeImmutable $valuationDate = null;

	private function __construct( int $id, Euro $amount, PaymentInterval $interval, ?PaymentReferenceCode $paymentReference ) {
		if ( $interval->isRecurring() ) {
			throw new \InvalidArgumentException( "Provided payment interval must be 0 (= one time payment) for Sofort payments." );
		}
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );
		$this->paymentReferenceCode = $paymentReference;
	}

	public static function create( int $id, Euro $amount, PaymentInterval $interval, PaymentReferenceCode $paymentReferenceCode ): self {
		return new self( $id, $amount, $interval, $paymentReferenceCode );
	}

	public function getPaymentReferenceCode(): string {
		if ( $this->paymentReferenceCode === null ) {
			return '';
		}
		return $this->paymentReferenceCode->getFormattedCode();
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	public function isBooked(): bool {
		return $this->valuationDate !== null && $this->transactionId !== null;
	}

	public function canBeBooked( array $transactionData ): bool {
		return $this->transactionId === null;
	}

	/**
	 * @param array<string,scalar> $transactionData Data from the payment provider
	 * @param PaymentIdRepository $idGenerator Not used here since we don't have followup payments
	 *
	 * @return Payment
	 */
	public function bookPayment( array $transactionData, PaymentIdRepository $idGenerator ): Payment {
		if ( !$this->canBeBooked( $transactionData ) ) {
			throw new DomainException( 'Payment is already completed' );
		}
		if ( empty( $transactionData['transactionId'] ) ) {
			throw new DomainException( 'Transaction ID missing' );
		}
		$this->transactionId = strval( $transactionData['transactionId'] );
		$valuationDate = DateTimeImmutable::createFromFormat( DateTimeInterface::ATOM, strval( $transactionData['valuationDate'] ) );
		if ( $valuationDate === false ) {
			$msg = 'Error in valuation date.';
			$errors = DateTimeImmutable::getLastErrors();
			if ( is_array( $errors ) ) {
				$msg .= ' ' . var_export( $errors, true );
			}
			throw new DomainException( $msg );
		}
		$valuationDate = $valuationDate->setTimezone( ValuationDateTimeZone::getTimeZone() );
		$this->valuationDate = $valuationDate;
		return $this;
	}

	protected function getPaymentName(): string {
		return self::PAYMENT_METHOD;
	}

	protected function getPaymentSpecificLegacyData(): array {
		$data = array_filter( [
			'transaction_id' => $this->transactionId ?: '',
			'valuation_date' => $this->valuationDate ? $this->valuationDate->format( 'Y-m-d H:i:s' ) : '',
		] );
		// always have the payment reference code in here, to enable override of existing code, just in case.
		$data['ueb_code'] = $this->paymentReferenceCode ? $this->paymentReferenceCode->getFormattedCode() : '';
		return $data;
	}

	public function anonymise(): void {
		$this->paymentReferenceCode = null;
	}

	public function getDisplayValues(): array {
		$parentValues = parent::getDisplayValues();
		$legacySubtypeValues = $this->getPaymentSpecificLegacyData();
		$subtypeValues = [];
		foreach ( $legacySubtypeValues as $key => $value ) {
			if ( isset( self::LEGACY_TO_DISPLAY_KEY_MAP[$key] ) ) {
				$subtypeValues[self::LEGACY_TO_DISPLAY_KEY_MAP[$key]] = $value;
			} else {
				$subtypeValues[$key] = $value;
			}
		}
		return array_merge(
			$parentValues,
			$subtypeValues
		);
	}

	public function isCompleted(): bool {
		return $this->isBooked();
	}
}
