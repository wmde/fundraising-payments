<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\AnonymizationException;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class DatabasePaymentAnonymizer implements PaymentAnonymizer {

	private const int BATCH_SIZE = 20;

	public function __construct(
		private readonly PaymentRepository $paymentRepository,
		private readonly EntityManager $entityManager
	) {
	}

	public function anonymizeWithIds( int ...$paymentIds ): void {
		$counter = 0;

		foreach ( $paymentIds as $id ) {
			try {
				$payment = $this->paymentRepository->getPaymentById( $id );
			} catch ( PaymentNotFoundException $e ) {
				throw new AnonymizationException( $e->getMessage() );
			}

			$payment->scrubPersonalData();
			$this->paymentRepository->storePayment( $payment );

			$counter++;
			if ( $counter % self::BATCH_SIZE === 0 ) {
				$this->entityManager->flush();
				$this->entityManager->clear();
			}
		}
	}
}
