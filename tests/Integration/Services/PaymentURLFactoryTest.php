<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Services\PaymentURLFactory;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\SofortClient;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

/**
 * This test prevents us from creating new payment subclasses without
 * extending the factory method in {@see PaymentURLFactory}.
 *
 * It is more of a static analysis than a unit test.
 */
#[CoversClass( PaymentURLFactory::class )]
class PaymentURLFactoryTest extends TestCase {

	/**
	 * @param class-string $className
	 */
	#[DataProvider( 'providePaymentClassNames' )]
	#[DoesNotPerformAssertions]
	public function testFactoryCreatesUrlGeneratorForPaymentSubclass( string $className ): void {
		$factory = new PaymentURLFactory(
			$this->createStub( CreditCardURLGeneratorConfig::class ),
			$this->createStub( LegacyPayPalURLGeneratorConfig::class ),
			$this->createStub( SofortURLGeneratorConfig::class ),
			$this->createStub( SofortClient::class ),
			'http://example.com/',
		);
		$authenticatorStub = $this->createStub( URLAuthenticator::class );
		$paymentReflection = new \ReflectionClass( $className );
		/** @var Payment $payment */
		$payment = $paymentReflection->newInstanceWithoutConstructor();

		// This will throw an exception when there is a new Payment subclass
		// for which we did not define a URL generator in the method
		$factory->createUrlGenerator( $payment, $authenticatorStub );
	}

	/**
	 * Prime {@see get_declared_classes()} by loading all files in the domain model namespace/file path
	 * that haven't been auto-loaded by previous tests
	 *
	 * @param string $paymentPath
	 */
	private static function loadAllPaymentClasses( string $paymentPath ): void {
		$phpFiles = glob( $paymentPath . '/*.php' );
		if ( empty( $phpFiles ) ) {
			throw new \LogicException( 'Path "' . $paymentPath . '" does not contain PHP files.' );
		}
		foreach ( $phpFiles as $classFilePath ) {
			require_once $classFilePath;
		}
	}

	/**
	 * @return iterable<array{class-string}>
	 */
	public static function providePaymentClassNames(): iterable {
		$paymentReflection = new \ReflectionClass( Payment::class );
		$paymentNamespace = $paymentReflection->getNamespaceName();

		self::loadAllPaymentClasses( dirname( $paymentReflection->getFileName() ?: '' ) );

		foreach ( get_declared_classes() as $class ) {
			$reflection = new \ReflectionClass( $class );
			if ( $reflection->isSubclassOf( Payment::class ) && $reflection->getNamespaceName() === $paymentNamespace ) {
				yield $reflection->getShortName() => [ $class ];
			}
		}
	}

}
