<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class DoctrinePaymentRepository implements PaymentRepository {

	public function __construct( private EntityManager $entityManager ) {
	}

	public function storePayment( Payment $payment ): void {
		// This will only throw when the ORM config setRejectIdCollisionInIdentityMap is set to true
		// The ORM checks for duplicate IDs while persisting
		try {
			$this->entityManager->persist( $payment );
		} catch ( EntityIdentityCollisionException $ex ) {
			throw new PaymentOverrideException( $ex->getMessage(), $ex->getCode(), $ex );
		}
		// This will only throw when the ORM config setRejectIdCollisionInIdentityMap is set to false
		// The database will throw an DBAL error
		try {
			$this->entityManager->flush();
		} catch ( UniqueConstraintViolationException $ex ) {
			throw new PaymentOverrideException( $ex->getMessage(), $ex->getCode(), $ex );
		}
	}

	public function getPaymentById( int $id ): Payment {
		$payment = $this->entityManager->find( Payment::class, $id );
		if ( $payment == null ) {
			throw new PaymentNotFoundException( sprintf( "Payment with id %d not found", $id ) );
		}
		return $payment;
	}

}
