<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer;

/**
 * Custom facade around he Sofort client library
 */
interface SofortClient {

	public function get( Request $request ): Response;
}
