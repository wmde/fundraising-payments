<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class DoctrinePaymentRepository implements PaymentRepository {

	public function __construct( private EntityManager $entityManager ) {
	}

	public function storePayment( Payment $payment ): void {
		$this->entityManager->persist( $payment );
		$this->entityManager->flush();
	}

	public function getPaymentById( int $id ): Payment {
		$payment = $this->entityManager->find( Payment::class, $id );
		if ( $payment == null ) {
			throw new PaymentNotFoundException( sprintf( "Payment with id %d not found", $id ) );
		}
		return $payment;
	}

}
