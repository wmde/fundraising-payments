<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;

class UniquePaymentReferenceCodeGenerator implements PaymentReferenceCodeGenerator {

	private PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator;
	/**
	 * @var EntityRepository<PaymentReferenceCode>
	 */
	private EntityRepository $entityRepository;

	public function __construct( PaymentReferenceCodeGenerator $paymentReferenceCodeGenerator, EntityManager $entityManager ) {
		$this->paymentReferenceCodeGenerator = $paymentReferenceCodeGenerator;
		$this->entityRepository = $entityManager->getRepository( PaymentReferenceCode::class );
	}

	public function newPaymentReference( string $prefix ): PaymentReferenceCode {
		do {
			$reference = $this->paymentReferenceCodeGenerator->newPaymentReference( $prefix );
		} while ( $this->codeIsNotUnique( $reference->getFormattedCode() ) );

		return $reference;
	}

	private function codeIsNotUnique( string $paymentReference ): bool {
		return !empty( $this->entityRepository->findBy( [ 'formattedCode' => $paymentReference ] ) );
	}
}
