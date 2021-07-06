<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use RuntimeException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\Request;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;

class Sofort {

	private const CURRENCY = 'EUR';

	private SofortConfig $config;
	private SofortClient $client;

	public function __construct( SofortConfig $config, SofortClient $client ) {
		$this->config = $config;
		$this->client = $client;
	}

	/**
	 * Generate a URL to use (refer the donor to) to finalize a purchase on a 3rd party payment provider page
	 *
	 * @param int $internalItemId Internal (WMDE-use only) Id of the item to pay
	 * @param string $externalItemId External (3rd parties may use to reference the item with this) Id of the item to pay
	 * @param Euro $amount The amount of money to pay
	 * @param string $updateToken A token to use to invoke our API to change payment details at a later point in time
	 * @param string $accessToken A token to use to return to the payment process after completing the 3rd party process
	 *
	 * @return string
	 */
	public function generateUrl( int $internalItemId, string $externalItemId, Euro $amount, string $updateToken, string $accessToken ): string {
		$request = new Request();
		$request->setAmount( $amount );
		$request->setCurrencyCode( self::CURRENCY );
		$request->setReasons( [ $this->config->getReasonText(), $externalItemId ] );
		$request->setSuccessUrl(
			$this->config->getReturnUrl() . '?' . http_build_query(
				[
					'id' => $internalItemId,
					'accessToken' => $accessToken
				]
			)
		);
		$request->setAbortUrl( $this->config->getCancelUrl() );
		$request->setNotificationUrl(
			$this->config->getNotificationUrl() . '?' . http_build_query(
				[
					'id' => $internalItemId,
					'updateToken' => $updateToken
				]
			)
		);
		$request->setLocale( $this->config->getLocale() );

		try {
			$response = $this->client->get( $request );
		}
		catch ( RuntimeException $exception ) {
			throw new RuntimeException( 'Could not generate Sofort URL: ' . $exception->getMessage() );
		}

		return $response->getPaymentUrl();
	}
}
