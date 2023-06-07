<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\KontoCheck;

use RuntimeException;
use WMDE\Fundraising\PaymentContext\Domain\BankDataGenerator;
use WMDE\Fundraising\PaymentContext\Domain\IbanValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\ExtendedBankData;
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
	 * @return ExtendedBankData
	 * @throws RuntimeException
	 */
	public function getBankDataFromAccountData( string $account, string $bankCode ): ExtendedBankData {
		$iban = \iban_gen( $bankCode, $account );

		if ( !$iban ) {
			throw new RuntimeException( 'Could not get IBAN' );
		}

		return new ExtendedBankData(
			new Iban( $iban ),
			\iban2bic( $iban ),
			$account,
			$bankCode,
			$this->bankNameFromBankCode( $bankCode )
		);
	}

	/**
	 * @param Iban $iban
	 * @return ExtendedBankData
	 * @throws \InvalidArgumentException
	 */
	public function getBankDataFromIban( Iban $iban ): ExtendedBankData {
		if ( $this->ibanValidator->validate( $iban->toString() )->hasViolations() ) {
			throw new \InvalidArgumentException( 'Provided IBAN should be valid' );
		}

		$bic = '';
		$account = '';
		$bankCode = '';
		$bankName = '';

		if ( $iban->getCountryCode() === 'DE' ) {
			$bic = \iban2bic( $iban->toString() );
			[ $account, $bankCode ] = $this->splitGermanIban( $iban );
			$bankName = $this->bankNameFromBankCode( $bankCode );
		}

		return new ExtendedBankData( $iban, $bic, $account, $bankCode, $bankName );
	}

	private function bankNameFromBankCode( string $bankCode ): string {
		$bankName = mb_convert_encoding( \lut_name( $bankCode ) ?: '', 'UTF-8', 'ISO-8859-1' );
		if ( is_string( $bankName ) ) {
			return $bankName;
		} elseif ( $bankName === false ) {
			// This should never happen
			throw new \UnexpectedValueException( "lut_name( $bankCode ) returned bank name that could not be transformed from ISO-8859-1 to UTF-8" );
		}
		// This return statement would only be reached if mb_convert_encoding was called with an array and is only here to make the method type-safe
		return '';
	}

	/**
	 * @param Iban $iban
	 * @return array{string,string}
	 */
	private function splitGermanIban( Iban $iban ): array {
		$ibanStr = $iban->toString();
		return [
			substr( $ibanStr, 12 ),
			substr( $ibanStr, 4, 8 )
		];
	}

}
