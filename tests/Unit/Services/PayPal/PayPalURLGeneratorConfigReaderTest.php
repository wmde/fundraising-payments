<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalURLGeneratorConfigReader;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalURLGeneratorConfigReader
 */
class PayPalURLGeneratorConfigReaderTest extends TestCase {
	public function testReadsExistingYamlFile(): void {
		$config = PayPalURLGeneratorConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_correct.yml' );
		$this->assertArrayHasKey( 'donation', $config );
	}

	public function testProductIdsMustBeUnique(): void {
		$this->expectException( \DomainException::class );
		$this->expectExceptionMessage( "All product IDs in the configuration file must be unique!" );
		$config = PayPalURLGeneratorConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_duplicate_product_id.yml' );
	}

	public function testSubscriptionPlanIdsMustBeUnique(): void {
		$this->expectException( \DomainException::class );
		$this->expectExceptionMessage( "All subscription plan IDs in the configuration file must be unique!" );
		$config = PayPalURLGeneratorConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_duplicate_plan_id.yml' );
	}
}
