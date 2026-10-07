<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Output\OutputInterface;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class DatabasePaymentAnonymizer implements PaymentAnonymizer {

	private const int BATCH_SIZE = 20;

	public function __construct(
		private readonly PaymentRepository $paymentRepository,
		private readonly EntityManager $entityManager,
		private readonly OutputInterface $output
	) {
	}

	public function anonymizeWithIds( int ...$paymentIds ): void {
		$counter = 0;

		foreach ( $paymentIds as $id ) {
			try {
				$payment = $this->paymentRepository->getPaymentById( $id );
				$payment->scrubPersonalData();
				$this->paymentRepository->storePayment( $payment );
			} catch ( PaymentNotFoundException $e ) {
				$this->output->writeln( "Failed to anonymize payment id: $id" );
				$this->output->writeln( $e->getMessage() );
			}

			$counter++;
			if ( $counter % self::BATCH_SIZE === 0 ) {
				$this->entityManager->flush();
				$this->entityManager->clear();
			}
		}
	}
}
