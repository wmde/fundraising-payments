<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer;

use WMDE\Euro\Euro;

class Request {

	private Euro $amount;
	private string $currencyCode = '';
	private string $successUrl = '';
	private string $abortUrl = '';
	private string $notificationUrl = '';
	/**
	 * @var string[]
	 */
	private array $reasons = [ '', '' ];
	private string $locale = '';

	public function getAmount(): Euro {
		return $this->amount;
	}

	public function setAmount( Euro $amount ): void {
		$this->amount = $amount;
	}

	public function getCurrencyCode(): string {
		return $this->currencyCode;
	}

	public function setCurrencyCode( string $currencyCode ): void {
		$this->currencyCode = $currencyCode;
	}

	public function getSuccessUrl(): string {
		return $this->successUrl;
	}

	public function setSuccessUrl( string $successUrl ): void {
		$this->successUrl = $successUrl;
	}

	public function getAbortUrl(): string {
		return $this->abortUrl;
	}

	public function setAbortUrl( string $abortUrl ): void {
		$this->abortUrl = $abortUrl;
	}

	public function getNotificationUrl(): string {
		return $this->notificationUrl;
	}

	public function setNotificationUrl( string $notificationUrl ): void {
		$this->notificationUrl = $notificationUrl;
	}

	/**
	 * @return string[]
	 */
	public function getReasons(): array {
		return $this->reasons;
	}

	/**
	 * @param string[] $reasons
	 */
	public function setReasons( array $reasons ): void {
		$this->reasons = $reasons;
	}

	public function getLocale(): string {
		return $this->locale;
	}

	public function setLocale( string $locale ): void {
		$this->locale = $locale;
	}
}
