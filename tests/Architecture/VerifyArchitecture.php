<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use WMDE\Euro\Euro;

class VerifyArchitecture {
	private const DOMAIN = 'WMDE\Fundraising\PaymentContext\Domain';
	private const USE_CASES = 'WMDE\Fundraising\PaymentContext\UseCases';
	private const DATA_ACCESS = 'WMDE\Fundraising\PaymentContext\DataAccess';
	private const SERVICES = 'WMDE\Fundraising\PaymentContext\Services';
	private const VENDOR_DOCTRINE = 'Doctrine';

	public function testDomainMayOnlyDependOnDomainLibraries(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::DOMAIN ) )
			->canOnlyDependOn()
			->classes(
				Selector::namespace( self::DOMAIN ),
				...$this->allowedDomainLibraries()
			);
	}

	/**
	 * @return SelectorInterface[]
	 */
	private function allowedDomainLibraries(): array {
		return [
			Selector::classname( Euro::class ),
			Selector::namespace( 'WMDE\FunValidators' ),
		];
	}

	public function testUseCasesMayOnlyDependOnDomainClassesAndServiceInterface(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::USE_CASES ) )
			->canOnlyDependOn()
			->classes(
				Selector::namespace( self::DOMAIN ),
				Selector::namespace( self::USE_CASES ),
				...$this->allowedDomainLibraries(),
				...$this->allowedServiceInterfaces()
			);
	}

	public function testDataAccessMayOnlyDependOnDomainClassesAndDoctrine(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::DATA_ACCESS ) )
			->canOnlyDependOn()
			->classes(
				Selector::namespace( self::DATA_ACCESS ),
				Selector::namespace( self::DOMAIN ),
				Selector::namespace( self::VENDOR_DOCTRINE ),
				...$this->allowedDomainLibraries(),
			);
	}

	/**
	 * @return SelectorInterface[]
	 */
	private function allowedServiceInterfaces(): array {
		return [
			Selector::AND(
				Selector::namespace( self::SERVICES ),
				Selector::interface()
			)
		];
	}

	public function testServiceInterfacesMayOnlyDependOnDomain(): Rule {
		return PHPat::rule()
			->classes( ...$this->allowedServiceInterfaces() )
			->canOnlyDependOn()
			->classes(
				Selector::namespace( self::DOMAIN ),
				...$this->allowedDomainLibraries(),
				...$this->allowedServiceInterfaces()
			);
	}

	public function testServicesMayOnlyDependOnDomainAndServices(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::SERVICES ) )
			->canOnlyDependOn()
			->classes(
				Selector::namespace( self::SERVICES ),
				Selector::namespace( self::DOMAIN ),
				Selector::namespace( self::DATA_ACCESS ),
				// TODO move use case DTOs into a separate namespace/function
				Selector::namespace( self::USE_CASES ),
				...$this->allowedDomainLibraries(),
				...$this->allowedServiceInterfaces(),
				// TODO split this, so Services don't depend on Doctrine but only DataAccess
				...$this->allowedVendorDependencies()
			);
	}

	public function testNoDependenciesOnTestCode(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( 'WMDE\Fundraising\PaymentContext' ) )
			->excluding( Selector::namespace( 'WMDE\Fundraising\PaymentContext\Tests' ) )
			->canOnlyDependOn()
			->classes(
				Selector::namespace( 'WMDE\Fundraising\PaymentContext' ),
				...$this->allowedDomainLibraries(),
				...$this->allowedVendorDependencies()
			)
			->excluding(
				Selector::namespace( 'WMDE\Fundraising\PaymentContext\Tests' ),
			);
	}

	/**
	 * @return SelectorInterface[]
	 */
	private function allowedVendorDependencies(): array {
		return [
			Selector::namespace( self::VENDOR_DOCTRINE ),
			Selector::namespace( 'GuzzleHttp' ),
			Selector::namespace( 'Sofort' ),
		];
	}
}
