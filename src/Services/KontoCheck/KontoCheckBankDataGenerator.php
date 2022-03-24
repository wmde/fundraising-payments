<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\KontoCheck;

use RuntimeException;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

class KontoCheckBankDataGenerator implements BankDataGenerator {

	private IbanValidator $ibanValidator;

	/**
	 * Mirrors the "OK" constant defined by the kontocheck extension
	 */
	private const KONTOCHECK_OK = 1;

	/**
	 * @param IbanValidator $ibanValidator
	 *
	 * @throws KontoCheckLibraryInitializationException
	 */
	public function __construct( IbanValidator $ibanValidator ) {
		$this->ibanValidator = $ibanValidator;

		$initializationResult = \lut_init();
		if ( $initializationResult !== self::KONTOCHECK_OK ) {
			throw new KontoCheckLibraryInitializationException( null, $initializationResult );
		}
	}

	/**
	 * @param string $account
	 * @param string $bankCode
	 * @return BankData
	 * @throws RuntimeException
	 */
	public function getBankDataFromAccountData( string $account, string $bankCode ): BankData {
		$iban = \iban_gen( $bankCode, $account );

		if ( !$iban ) {
			throw new RuntimeException( 'Could not get IBAN' );
		}

		return new BankData(
			new Iban( $iban ),
			\iban2bic( $iban ),
			$account,
			$bankCode,
			$this->bankNameFromBankCode( $bankCode )
		);
	}

	/**
	 * @param Iban $iban
	 * @return BankData
	 * @throws \InvalidArgumentException
	 */
	public function getBankDataFromIban( Iban $iban ): BankData {
		if ( $this->ibanValidator->validate( $iban->toString() )->hasViolations() ) {
			throw new \InvalidArgumentException( 'Provided IBAN should be valid' );
		}

		$bic = '';
		$account = '';
		$bankCode = '';
		$bankName = '';

		if ( $iban->getCountryCode() === 'DE' ) {
			$bic = \iban2bic( $iban->toString() );
			$account = $iban->accountNrFromDeIban();
			$bankCode = $iban->bankCodeFromDeIban();
			$bankName = $this->bankNameFromBankCode( $bankCode );
		}

		return new BankData( $iban, $bic, $account, $bankCode, $bankName );
	}

	private function bankNameFromBankCode( string $bankCode ): string {
		return utf8_encode( \lut_name( $bankCode ) );
	}

}
