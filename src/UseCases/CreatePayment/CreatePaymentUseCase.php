<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class CreatePaymentUseCase {
	public function __construct(
		private PaymentIDRepository $idGenerator,
		private PaymentRepository $paymentRepository ) {
	}

	public function createPayment( PaymentCreationRequest $request ): SuccessResponse|FailureResponse {
		// TODO refactor validation into individual methods
		//      maybe builder pattern or wrapper exception

		try {
			$interval = PaymentInterval::from( $request->interval );
		} catch ( \ValueError $e ) {
			return new FailureResponse( sprintf(
				'Invalid Interval. Got the following exception message: %s',
				$e->getMessage()
			) );
		}

		try {
			$amount = Euro::newFromCents( $request->amountInEuroCents );
		} catch ( \InvalidArgumentException $e ) {
			return new FailureResponse( sprintf(
				'Invalid amount. Got the following exception message: %s',
				$e->getMessage()
			) );
		}

		switch ( $request->paymentType ) {
			case 'MCP':
				$payment = new CreditCardPayment(
					$this->getNextIdOnce(),
					$amount,
					$interval
				);
				break;
			// TODO implement BEZ, PPL, SUB and UEB
			default:
				return new FailureResponse( "Invalid payment type: " . $request->paymentType );
		}

		$this->paymentRepository->storePayment( $payment );
		return new SuccessResponse( $this->getNextIdOnce() );
	}

	private function getNextIdOnce(): int {
		static $id = null;
		if ( $id === null ) {
			$id = $this->idGenerator->getNewID();
		}
		return $id;
	}

}
