<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\BookPayment;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\BookPayment\FailureResponse
 */
class FailureResponseTest extends TestCase {
	public function testWhenUsingAlreadyCompletedConstructor_isAlreadyCompletedReturnsTrue(): void {
		$response = FailureResponse::newAlreadyCompletedResponse();

		$this->assertTrue( $response->paymentWasAlreadyCompleted() );
	}

	public function testWhenStringConstructor_isAlreadyCompletedReturnsFalse(): void {
		$response = new FailureResponse( 'Could not book payment for ... reasons.' );

		$this->assertFalse( $response->paymentWasAlreadyCompleted() );
	}
}
