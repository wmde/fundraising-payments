<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

class Product {

	public function __construct(
		public readonly string $name,
		public readonly ?string $id = null,
		public readonly ?string $description = null,
	) {
	}

	public function toJSON(): string {
		return json_encode( [
			"name" => $this->name,
			"id" => $this->id,
			"description" => $this->description,
			"category" => "NONPROFIT",
			"type" => "SERVICE"
		], JSON_THROW_ON_ERROR );
	}
}
