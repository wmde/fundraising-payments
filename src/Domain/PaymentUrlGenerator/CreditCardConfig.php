<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCardConfig {

	private const CONFIG_KEY_BASE_URL = 'base-url';
	private const CONFIG_KEY_PROJECT_ID = 'project-id';
	private const CONFIG_KEY_LOCALE = 'locale';
	private const CONFIG_KEY_BACKGROUND_COLOR = 'background-color';
	private const CONFIG_KEY_LOGO = 'logo';
	private const CONFIG_KEY_THEME = 'theme';
	private const CONFIG_KEY_TESTMODE = 'testmode';

	private string $baseUrl;
	private string $projectId;
	private string $locale;
	private string $backgroundColor;
	private string $logo;
	private string $theme;
	private bool $testMode;

	private function __construct( string $baseUrl, string $projectId, string $locale, string $backgroundColor, string $logo, string $theme,
		bool $testMode ) {
		$this->baseUrl = $baseUrl;
		$this->projectId = $projectId;
		$this->locale = $locale;
		$this->backgroundColor = $backgroundColor;
		$this->logo = $logo;
		$this->theme = $theme;
		$this->testMode = $testMode;
	}

	/**
	 * @param array $config
	 *
	 * @return self
	 * @throws \RuntimeException
	 */
	public static function newFromConfig( array $config ): self {
		return ( new self(
			$config[self::CONFIG_KEY_BASE_URL],
			$config[self::CONFIG_KEY_PROJECT_ID],
			$config[self::CONFIG_KEY_LOCALE],
			$config[self::CONFIG_KEY_BACKGROUND_COLOR],
			$config[self::CONFIG_KEY_LOGO],
			$config[self::CONFIG_KEY_THEME],
			$config[self::CONFIG_KEY_TESTMODE]
		) )->assertNoEmptyFields();
	}

	private function assertNoEmptyFields(): self {
		foreach ( get_object_vars( $this ) as $fieldName => $fieldValue ) {
			if ( !isset( $fieldValue ) || $fieldValue === '' ) {
				throw new \RuntimeException( "Configuration variable '$fieldName' can not be empty" );
			}
		}

		return $this;
	}

	public function getBaseUrl(): string {
		return $this->baseUrl;
	}

	public function getProjectId(): string {
		return $this->projectId;
	}

	public function getLocale(): string {
		return $this->locale;
	}

	public function getBackgroundColor(): string {
		return $this->backgroundColor;
	}

	public function getLogo(): string {
		return $this->logo;
	}

	public function getTheme(): string {
		return $this->theme;
	}

	public function isTestMode(): bool {
		return $this->testMode;
	}

}
