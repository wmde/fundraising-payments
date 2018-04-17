<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

abstract class BasePaymentMethod implements PaymentMethod {
	/**
	 * @param callable $callback
	 * @return mixed|void
	 */
	public function dispatch( callable $callback ) {
		return ( new PaymentMethodDispatcher( [ $this->getId() => $callback ] ) )->dispatch( $this );
	}
}