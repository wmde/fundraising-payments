<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentReferenceCodeGenerator;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\CharacterIndexGenerator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\PaymentReferenceCodeGenerator;

class UniquePaymentReferenceCodeGenerator implements PaymentReferenceCodeGenerator {

	private PaymentReferenceCodeGenerator $randomPaymentReferenceGenerator;
	private Connection $connection;

	public function __construct( CharacterIndexGenerator $characterIndexGenerator, EntityManager $entityManager ) {
		$this->randomPaymentReferenceGenerator = new RandomPaymentReferenceCodeGenerator( $characterIndexGenerator );
		$this->connection = $entityManager->getConnection();
	}

	public function newPaymentReference( string $prefix ): PaymentReferenceCode {
		do {
			$reference = $this->randomPaymentReferenceGenerator->newPaymentReference( $prefix );
		} while ( $this->codeIsNotUnique( $reference->getFormattedCode() ) );

		return $reference;
	}

	private function codeIsNotUnique( string $paymentReference ): bool {
		$result = $this->connection->executeQuery("SELECT COUNT(*) as count FROM (
			SELECT payment_reference_code FROM payments_bank_transfer WHERE payment_reference_code = '{$paymentReference}'
			UNION ALL
			SELECT payment_reference_code FROM payments_sofort WHERE payment_reference_code = '{$paymentReference}'
		) AS count_table" );

		$data = $result->fetchAssociative();
		return $data && $data['count'] > 0;
	}
}
