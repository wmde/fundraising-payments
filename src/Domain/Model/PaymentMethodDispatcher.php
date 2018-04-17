<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

class PaymentMethodDispatcher {
	private $callbackTable;

	/**
	 * @param callable[] $callbackTable payment id => function
	 */
	public function __construct( array $callbackTable ) {
		$this->callbackTable = $callbackTable;
	}

	public function dispatch( PaymentMethod $method ) {
		if ( empty( $this->callbackTable[$method->getId()] ) ) {
			return;
		}
		return call_user_func( $this->callbackTable[$method->getId()], $method );
	}
}