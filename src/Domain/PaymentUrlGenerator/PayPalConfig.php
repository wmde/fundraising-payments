<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class PayPalConfig {

	public const CONFIG_KEY_ACCOUNT_ADDRESS = 'account-address';
	public const CONFIG_KEY_LOCALE = 'locale';
	public const CONFIG_KEY_BASE_URL = 'base-url';
	public const CONFIG_KEY_NOTIFY_URL = 'notify-url';
	public const CONFIG_KEY_RETURN_URL = 'return-url';
	public const CONFIG_KEY_CANCEL_URL = 'cancel-url';
	public const CONFIG_KEY_DELAY_IN_DAYS = 'delay-in-days';

	private string $payPalAccountAddress;
	private string $locale;
	private string $payPalBaseUrl;
	private string $notifyUrl;
	private string $returnUrl;
	private string $cancelUrl;
	private int $delayInDays;
	private TranslatableDescription $translatableDescription;

	private function __construct( string $payPalAccountAddress, string $locale, string $payPalBaseUrl, string $notifyUrl,
		string $returnUrl, string $cancelUrl, int $delayInDays, TranslatableDescription $translatableDescription ) {
		$this->payPalAccountAddress = $payPalAccountAddress;
		$this->locale = $locale;
		$this->payPalBaseUrl = $payPalBaseUrl;
		$this->notifyUrl = $notifyUrl;
		$this->returnUrl = $returnUrl;
		$this->cancelUrl = $cancelUrl;
		$this->delayInDays = $delayInDays;
		$this->translatableDescription = $translatableDescription;
	}

	/**
	 * @param array{ 'account-address': string, 'locale' : string, 'base-url': string, 'notify-url': string, 'return-url': string, 'cancel-url': string, 'delay-in-days'?: int } $config
	 * @param TranslatableDescription $translatableDescription
	 *
	 * @return PayPalConfig
	 * @throws RuntimeException
	 */
	public static function newFromConfig( array $config, TranslatableDescription $translatableDescription ): self {
		return ( new self(
			$config[self::CONFIG_KEY_ACCOUNT_ADDRESS],
			$config[self::CONFIG_KEY_LOCALE],
			$config[self::CONFIG_KEY_BASE_URL],
			$config[self::CONFIG_KEY_NOTIFY_URL],
			$config[self::CONFIG_KEY_RETURN_URL],
			$config[self::CONFIG_KEY_CANCEL_URL],
			isset( $config[self::CONFIG_KEY_DELAY_IN_DAYS] ) ? (int)$config[self::CONFIG_KEY_DELAY_IN_DAYS] : -1,
			$translatableDescription
		) )->assertNoEmptyFields();
	}

	private function assertNoEmptyFields(): self {
		foreach ( get_object_vars( $this ) as $fieldName => $fieldValue ) {
			if ( empty( $fieldValue ) ) {
				throw new RuntimeException( "Configuration variable '$fieldName' can not be empty" );
			}
		}

		return $this;
	}

	public function getPayPalAccountAddress(): string {
		return $this->payPalAccountAddress;
	}

	public function getLocale(): string {
		return $this->locale;
	}

	public function getPayPalBaseUrl(): string {
		return $this->payPalBaseUrl;
	}

	public function getNotifyUrl(): string {
		return $this->notifyUrl;
	}

	public function getReturnUrl(): string {
		return $this->returnUrl;
	}

	public function getCancelUrl(): string {
		return $this->cancelUrl;
	}

	public function getDelayInDays(): int {
		return $this->delayInDays;
	}

	public function getTranslatableDescription(): TranslatableDescription {
		return $this->translatableDescription;
	}
}
