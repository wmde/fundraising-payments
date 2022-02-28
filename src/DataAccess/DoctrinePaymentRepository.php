<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class DoctrinePaymentRepository implements PaymentRepository {

	public function __construct( private EntityManager $entityManager ) {
	}

	public function storePayment( Payment $payment ): void {
		$this->entityManager->persist( $payment );
		try {
			$this->entityManager->flush();
		}
		// catch might be an ORMException in the future, see https://github.com/doctrine/orm/issues/7780
		catch ( UniqueConstraintViolationException $ex ) {
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
