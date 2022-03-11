<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

class PayPalPaymentSpy {
	/** @var \ReflectionClass<PayPalPayment> */
	private \ReflectionClass $reflectedClass;

	public function __construct( private PayPalPayment $inspectionObject ) {
		$this->reflectedClass = new \ReflectionClass( $inspectionObject );
	}

	private function getPrivateValue( string $propertyName ): mixed {
		if ( !$this->reflectedClass->hasProperty( $propertyName ) ) {
			throw new \LogicException( sprintf(
				"Property %s not found in class %s. Try re-generating the class %s",
				$propertyName, $this->reflectedClass->getName(), self::class
			) );
		}
		$prop = $this->reflectedClass->getProperty( $propertyName );
		$prop->setAccessible( true );
		return $prop->getValue( $this->inspectionObject );
	}

	/**
	 * @psalm-suppress MixedReturnTypeCoercion
	 * @return array<string,mixed>
	 */
	public function getBookingData(): array {
		$value = $this->getPrivateValue( 'bookingData' );
		assert( is_array( $value ) );
		return $value;
	}

	public function getValuationDate(): ?\DateTimeImmutable {
		$value = $this->getPrivateValue( 'valuationDate' );
		assert( $value === null || $value instanceof \DateTimeImmutable );
		return $value;
	}

	public function getId(): int {
		$value = $this->getPrivateValue( 'id' );
		assert( is_int( $value ) );
		return $value;
	}

	public function getAmount(): \WMDE\Euro\Euro {
		$value = $this->getPrivateValue( 'amount' );
		assert( $value instanceof \WMDE\Euro\Euro );
		return $value;
	}

	public function getInterval(): \WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval {
		$value = $this->getPrivateValue( 'interval' );
		assert( $value instanceof \WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval );
		return $value;
	}

	public function getPaymentMethod(): string {
		$value = $this->getPrivateValue( 'paymentMethod' );
		assert( is_string( $value ) );
		return $value;
	}
}
