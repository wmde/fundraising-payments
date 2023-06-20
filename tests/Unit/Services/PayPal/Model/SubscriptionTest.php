<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription
 */
class SubscriptionTest extends TestCase {

	public function testCreateFromApiResponse(): void {
		$subscription = Subscription::from( [
			'id' => '1',
			'start_time' => '2023-12-24T01:02:03Z',
			'links' => [
				[
					"href" => "https://api-m.paypal.com/v1/billing/subscriptions/I-BW452GLLEP1G",
					"rel" => "self",
					"method" => "GET"
				],
				[
					"href" => "https://www.paypal.com/webapps/billing/subscriptions?ba_token=BA-2M539689T3856352J",
					"rel" => "approve",
					"method" => "GET"
				]
			]
		] );

		$this->assertEquals(
			new Subscription(
				'1',
				new \DateTimeImmutable( '2023-12-24T01:02:03Z' ),
				'https://www.paypal.com/webapps/billing/subscriptions?ba_token=BA-2M539689T3856352J'
			),
			$subscription
		);
	}

	/**
	 * @dataProvider responsesWithMissingFields
	 * @param array<string,string|bool> $apiResponse
	 */
	public function testIdAndStartTimeAreRequiredField( array $apiResponse ): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( 'Fields "id" and "start_time" are required' );
		Subscription::from( $apiResponse );
	}

	/**
	 * @return iterable<array{array<string,string|bool>}>
	 */
	public static function responsesWithMissingFields(): iterable {
		yield [ [ 'start_time' => '2023-01-02T03:04:05Z' ] ];
		yield [ [ 'id' => 'id1' ] ];
		yield [ [] ];
		yield [ [ 'id' => false ] ];
	}

	/**
	 * @dataProvider malformedDates
	 */
	public function testMalformedStartTimeThrowsAnException( mixed $malformedDate ): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( 'Malformed date formate for start_time' );
		Subscription::from( [ 'id' => 'id-5', 'start_time' => $malformedDate ] );
	}

	/**
	 * @return iterable<mixed>
	 */
	public static function malformedDates(): iterable {
		yield [ 123456576 ];
		yield [ '' ];
		yield [ 'bad date' ];
	}

	public function testUnsetLinksThrowsException(): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( "Fields must contain array with links!" );
		Subscription::from( [ 'id' => 'id-5', 'start_time' => '2023-01-02T03:04:05Z' ] );
	}

	public function testMissingApprovalLinksThrowsAnException(): void {
		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessage( "Link array did not contain approval link!" );
		Subscription::from( [
			'id' => 'id-5',
			'start_time' => '2023-01-02T03:04:05Z',
			'links' => [
				[
					"href" => "https://api-m.paypal.com/v1/billing/subscriptions/I-BW452GLLEP1G",
					"rel" => "self",
					"method" => "GET"
				]
			]
		] );
	}

}
