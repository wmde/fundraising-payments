<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PaymentWithoutAssociatedData implements PaymentMethod {

	private $paymentMethod;

	public function __construct( string $paymentMethodId ) {
		$this->paymentMethod = $paymentMethodId;
	}

	public function getId(): string {
		return $this->paymentMethod;
	}

	public function hasExternalProvider(): bool {
		return false;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return null;
	}

}
