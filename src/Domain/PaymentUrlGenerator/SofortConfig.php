<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

class SofortConfig {

	private string $reasonText;
	private string $locale;
	private string $returnUrl;
	private string $cancelUrl;
	private string $notificationUrl;

	public function __construct( string $reasonText, string $locale, string $returnUrl, string $cancelUrl, string $notificationUrl ) {
		$this->reasonText = $reasonText;
		$this->locale = $locale;
		$this->returnUrl = $returnUrl;
		$this->cancelUrl = $cancelUrl;
		$this->notificationUrl = $notificationUrl;
	}

	public function getReasonText(): string {
		return $this->reasonText;
	}

	public function getLocale(): string {
		return $this->locale;
	}

	public function getReturnUrl(): string {
		return $this->returnUrl;
	}

	public function getCancelUrl(): string {
		return $this->cancelUrl;
	}

	public function getNotificationUrl(): string {
		return $this->notificationUrl;
	}
}
