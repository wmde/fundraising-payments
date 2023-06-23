<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

class SofortURLGeneratorConfig {

	public function __construct(
		private string $locale,
		private string $returnUrl,
		private string $cancelUrl,
		private string $notificationUrl,
		private TranslatableDescription $translatableDescription ) {
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

	public function getTranslatableDescription(): TranslatableDescription {
		return $this->translatableDescription;
	}
}
