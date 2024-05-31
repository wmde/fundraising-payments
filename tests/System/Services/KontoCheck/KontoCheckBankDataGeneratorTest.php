<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\KontoCheck;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Services\KontoCheck\KontoCheckBankDataGenerator;
use WMDE\Fundraising\PaymentContext\Services\KontoCheck\KontoCheckIbanValidator;

#[CoversClass( KontoCheckBankDataGenerator::class )]
#[CoversClass( ExtendedBankData::class )]
#[RequiresPhpExtension( 'konto_check' )]
class KontoCheckBankDataGeneratorTest extends TestCase {

	public function testWhenUsingConfigLutPath_constructorCreatesConverter(): void {
		$this->assertInstanceOf( KontoCheckBankDataGenerator::class, $this->newBankDataConverter() );
	}

	#[DataProvider( 'ibanTestProvider' )]
	public function testWhenGivenInvalidIban_converterThrowsException( string $ibanToTest ): void {
		$bankConverter = $this->newBankDataConverter();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provided IBAN should be valid' );
		$bankConverter->getBankDataFromIban( new Iban( $ibanToTest ) );
	}

	/**
	 * @return array<int,array{string}>
	 */
	public static function ibanTestProvider(): array {
		return [
			[ '' ],
			[ 'DE120105170648489892' ],
			[ 'DE1048489892' ],
			[ 'BE125005170648489890' ],
		];
	}

	public function testWhenGivenValidIban_converterReturnsBankData(): void {
		$bankConverter = $this->newBankDataConverter();

		$bankData = new ExtendedBankData(
			new Iban( iban: 'DE12500105170648489890' ),
			bic: 'INGDDEFFXXX',
			account: '0648489890',
			bankCode: '50010517',
			bankName: 'ING-DiBa'
		);

		$this->assertEquals(
			$bankData,
			$bankConverter->getBankDataFromIban( new Iban( 'DE12500105170648489890' ) )
		);
	}

	public function testWhenGivenValidNonDEIban_converterReturnsIBAN(): void {
		$bankConverter = $this->newBankDataConverter();

		$bankData = new ExtendedBankData(
			new Iban( iban:'BE68844010370034' ),
			bic:'',
			account: '',
			bankCode: '',
			bankName: ''
		);

		$this->assertEquals(
			$bankData,
			$bankConverter->getBankDataFromIban( new Iban( 'BE68844010370034' ) )
		);
	}

	#[DataProvider( 'accountTestProvider' )]
	public function testWhenGivenInvalidAccountData_converterThrowsException( string $accountToTest, string $bankCodeToTest ): void {
		$bankConverter = $this->newBankDataConverter();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not get IBAN' );
		$bankConverter->getBankDataFromAccountData( $accountToTest, $bankCodeToTest );
	}

	/**
	 * @return array<int,array{string,string}>
	 */
	public static function accountTestProvider(): array {
		return [
			[ '', '' ],
			[ '0648489890', '' ],
			[ '0648489890', '12310517' ],
			[ '1234567890', '50010517' ],
			[ '', '50010517' ],
		];
	}

	public function testWhenGivenValidAccountData_converterReturnsBankData(): void {
		$bankConverter = $this->newBankDataConverter();

		$bankData = new ExtendedBankData(
			iban: new Iban( 'DE12500105170648489890' ),
			bic: 'INGDDEFFXXX',
			account: '0648489890',
			bankCode: '50010517',
			bankName: 'ING-DiBa'
		);

		$this->assertEquals(
			$bankData,
			$bankConverter->getBankDataFromAccountData( '0648489890', '50010517' )
		);
	}

	private function newBankDataConverter(): BankDataGenerator {
		return new KontoCheckBankDataGenerator( new KontoCheckIbanValidator() );
	}

}
