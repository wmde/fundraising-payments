<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\DataAccess\Backup;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * The class provides backup configuration data for the backup classes of the bounded contexts.
 *
 * The bounded contexts are responsible for marking their data das "backed up", this class is just here to provide
 * a subset of payment data for the bounded context and does not provide a `doBackup` method that changes payment state.
 */
class PersonalDataBackup {
	public function __construct( private readonly EntityManager $entityManager ) {
	}

	/**
	 * @param string $subSelectForId
	 * @return TableBackupConfiguration[]
	 */
	public function getTableBackupConfigurationForContext( string $subSelectForId ): array {
		if ( !preg_match( '/^\s*select.*from/i', $subSelectForId ) ) {
			throw new \LogicException( 'You must give a SQL SELECT query as condition' );
		}

		$paymentTypesWithBackup = [
			BankTransferPayment::class,
			DirectDebitPayment::class,
			CreditCardPayment::class,
			PayPalPayment::class
		];

		$subTables = [ 'payment' ];

		foreach ( $paymentTypesWithBackup as $paymentClass ) {
			$metadata = $this->entityManager->getClassMetadata( $paymentClass );
			$subTables[] = $metadata->getTableName();
		}

		$subTables = array_unique( $subTables );

		return [
			new TableBackupConfiguration( implode( ' ', $subTables ), sprintf( 'id IN ( %s )', $subSelectForId ) )
		];
	}

}
