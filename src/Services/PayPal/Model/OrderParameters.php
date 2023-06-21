<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

use WMDE\Euro\Euro;

class OrderParameters {

	public function __construct(
		public readonly string $invoiceId,
		public readonly string $orderId,
		public readonly string $itemName,
		public readonly Euro $amount,
		public readonly string $returnUrl,
		public readonly string $cancelUrl,
	) {
	}

	public function toJSON(): string {
		$euroAmount = [
			'currency_code' => 'EUR',
			'value' => $this->amount->getEuroString(),
		];

		return json_encode(
			[
				'purchase_units' =>
					[
						[
							'reference_id' => $this->orderId,
							'invoice_id' => $this->invoiceId,
							'items' =>
								[
									[
										'name' => $this->itemName,
										'quantity' => '1',
										'category' => 'DONATION',
										'unit_amount' => $euroAmount
									],
								],
							'amount' =>
								[
									...$euroAmount,
									'breakdown' =>
										[
											'item_total' => $euroAmount
										],
								],
						],
					],
				'intent' => 'CAPTURE',
				'application_context' =>
					[
						'brand_name' => 'Wikimedia Deutschland',
						'landing_page' => 'LOGIN',
						'user_action' => 'CONTINUE',
						'return_url' => $this->returnUrl,
						'cancel_url' => $this->cancelUrl,
					],
			],
		 JSON_THROW_ON_ERROR );
	}
}
