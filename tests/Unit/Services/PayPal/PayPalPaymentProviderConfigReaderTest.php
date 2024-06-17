<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Exception\ParseException;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfigReader;

#[CoversClass( PayPalPaymentProviderAdapterConfigReader::class )]
class PayPalPaymentProviderConfigReaderTest extends TestCase {
	public function testReadsExistingYamlFile(): void {
		$config = PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_correct.yml' );
		$this->assertArrayHasKey( 'donation', $config );
	}

	public function testProductIdsMustBeUnique(): void {
		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( "All product IDs in the configuration file must be unique!" );
		PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_duplicate_product_id.yml' );
	}

	public function testSubscriptionPlanIdsMustBeUnique(): void {
		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( "All subscription plan IDs in the configuration file must be unique!" );
		PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_duplicate_plan_id.yml' );
	}

	public function testFileMustContainAtLeastOneLanguage(): void {
		$this->expectException( InvalidConfigurationException::class );
		$this->expectExceptionMessage( 'The path "paypal_api.donation" should have at least 1 element(s) defined.' );
		PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_no_language.yml' );
	}

	public function testFileMustContainAtLeastOnePlan(): void {
		$this->expectException( InvalidConfigurationException::class );
		$this->expectExceptionMessage( 'The child config "product_id" under "paypal_api.donation.de_DE" must be configured.' );
		PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_no_plans.yml' );
	}

	public function testReadingFromEmptyFileThrowsException(): void {
		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( 'Configuration file must contain a nested array structure!' );
		PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_empty.yml' );
	}

	public function testReadingFromNonexistentFileThrowsException(): void {
		$this->expectException( ParseException::class );
		$this->expectExceptionMessageMatches( '#does/not/exist/plan.yml" does not exist#' );
		PayPalPaymentProviderAdapterConfigReader::readConfig( __DIR__ . '/this/path/does/not/exist/plan.yml' );
	}
}
