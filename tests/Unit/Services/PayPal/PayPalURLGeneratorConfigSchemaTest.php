<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalURLGeneratorConfigSchema;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalURLGeneratorConfigSchema
 */
class PayPalURLGeneratorConfigSchemaTest extends TestCase {
	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidateSchema(): void {
		$config = Yaml::parseFile(
			__DIR__ . '/../../../Data/PayPalAPIURLGeneratorConfig/paypal_api_correct.yml'
		);

		$processor = new Processor();
		$schema = new PayPalURLGeneratorConfigSchema();
		$processor->processConfiguration(
			$schema,
			[ $config ]
		);
	}
}
