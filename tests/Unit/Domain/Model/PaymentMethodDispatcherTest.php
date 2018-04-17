<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethodDispatcher;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentMethodStub;

class PaymentMethodDispatcherTest extends TestCase {

	public function testDispatchCallsMatchingDispatchFunction() {
		$testPayment = new PaymentMethodStub();
		$dispatcher = new PaymentMethodDispatcher( [
			$testPayment->getId() => function ( PaymentMethodStub $payment ) use ( $testPayment ) {
				$this->assertSame( $testPayment, $payment );
			}
		] );

		$dispatcher->dispatch( $testPayment );
	}

	public function testDispatchChecksCallbackParameterForPaymentType() {
		$testPayment = new PaymentMethodStub();
		$dispatcher = new PaymentMethodDispatcher( [
			$testPayment->getId() => function ( BankTransferPayment $payment ) {
				$this->fail( 'This should not be called' );
			}
		] );

		$this->expectException( \TypeError::class );
		$dispatcher->dispatch( $testPayment );
	}

	public function testDispatchReturnsCallbackResult() {
		$testPayment = new PaymentMethodStub();
		$dispatcher = new PaymentMethodDispatcher( [
			$testPayment->getId() => function ( PaymentMethodStub $payment ) {
				return 'let me see';
			}
		] );

		$this->assertSame( 'let me see', $dispatcher->dispatch( $testPayment ) );
	}
}
