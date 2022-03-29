<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentRepository;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\DataAccess\PaymentOverrideException;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\BankTransferPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\CreditCardPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\DirectDebitPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\PayPalPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\SofortPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentRepository
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Euro
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\PaymentInterval
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\PaymentReferenceCode
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Iban
 */
class DoctrinePaymentRepositoryTest extends TestCase {

	private Connection $connection;
	private EntityManager $entityManager;

	private const IBAN = 'DE00123456789012345678';
	private const BIC = 'SCROUSDBXXX';

	protected function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
		$this->connection = $this->entityManager->getConnection();
	}

	public function testStoreCreditCardPayment(): void {
		$payment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$payment->bookPayment( [ 'transactionId' => 'badcaffee' ] );
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawCreditCardPaymentData();
		$this->assertSame( 9900, $insertedPayment['amount'] );
		$this->assertSame( 3, $insertedPayment['payment_interval'] );
		$this->assertSame( 'MCP', $insertedPayment['payment_method'] );
		$this->assertNotNull( $insertedPayment['valuation_date'] );
		$this->assertSame( '{"transactionId":"badcaffee"}', $insertedPayment['booking_data'] );
	}

	public function testRepositoryPreventsOverridingPaymentsWithTheSameId(): void {
		$firstPayment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$firstPayment->bookPayment( [ 'transactionId' => 'badcaffee' ] );
		$secondPayment = new CreditCardPayment( 1, Euro::newFromInt( 42 ), PaymentInterval::Monthly );
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$repo->storePayment( $firstPayment );

		$this->expectException( PaymentOverrideException::class );

		$repo->storePayment( $secondPayment );
	}

	public function testFindCreditCardPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawBookedCreditCardData();

		$payment = $repo->getPaymentById( 1 );

		$this->assertInstanceOf( CreditCardPayment::class, $payment );
		$paymentSpy = new CreditCardPaymentInspector( $payment );
		$this->assertSame( 4223, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $paymentSpy->getInterval() );
		$this->assertEquals( new \DateTimeImmutable( '2021-12-24 23:00:00' ), $paymentSpy->getValuationDate() );
		$this->assertSame( [ 'transactionId' => '1eetcaffee' ], $paymentSpy->getBookingData() );
	}

	public function testStorePayPalPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$bookingData = PayPalPaymentBookingData::newValidBookingData();

		$payment->bookPayment( $bookingData );
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawPayPalPaymentData();
		$this->assertSame( 9900, $insertedPayment['amount'] );
		$this->assertSame( 3, $insertedPayment['payment_interval'] );
		$this->assertSame( 'PPL', $insertedPayment['payment_method'] );
		$this->assertSame( '2022-01-01 01:01:01', $insertedPayment['valuation_date'] );
		$this->assertSame( PayPalPaymentBookingData::newEncodedValidBookingData(), $insertedPayment['booking_data'] );
	}

	public function testFindPayPalPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawPayPalData();

		$payment = $repo->getPaymentById( 1 );
		$this->assertInstanceOf( PayPalPayment::class, $payment );

		$paymentSpy = new PayPalPaymentInspector( $payment );
		$this->assertSame( 4223, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $paymentSpy->getInterval() );
		$this->assertEquals( new \DateTimeImmutable( '2021-12-24 23:00:00' ), $paymentSpy->getValuationDate() );
		$this->assertSame( [
			'item_number' => "1",
			'mc_currency' => 'EUR',
			'mc_fee' => '2.70',
			'mc_gross' => '2.70',
			'payer_email' => 'foerderpp@wikimedia.de',
			'payer_id' => '42',
			'payer_status' => 'verified',
			'payment_date' => '2022-01-01 01:01:01',
			'payment_status' => 'processed',
			'payment_type' => 'instant',
			'settle_amount' => '2.70',
			'subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'txn_id' => '4242',
		], $paymentSpy->getBookingData() );
	}

	public function testStoreDirectDebitPayment(): void {
		$payment = DirectDebitPayment::create( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly, new Iban( self::IBAN ), self::BIC );

		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawDirectDebitPaymentData();
		$this->assertSame( 9900, $insertedPayment['amount'] );
		$this->assertSame( 3, $insertedPayment['payment_interval'] );
		$this->assertSame( 'BEZ', $insertedPayment['payment_method'] );
		$this->assertSame( self::IBAN, $insertedPayment['iban'] );
		$this->assertSame( self::BIC, $insertedPayment['bic'] );
		$this->assertSame( 0, $insertedPayment['is_cancelled'] );
	}

	public function testStoreAnonymisedDirectDebitPayment(): void {
		$payment = DirectDebitPayment::create( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly, new Iban( self::IBAN ), self::BIC );
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$payment->anonymise();
		$repo->storePayment( $payment );

		$updatedPayment = $this->fetchRawDirectDebitPaymentData();
		$this->assertSame( 9900, $updatedPayment['amount'] );
		$this->assertSame( 3, $updatedPayment['payment_interval'] );
		$this->assertSame( 'BEZ', $updatedPayment['payment_method'] );
		$this->assertNull( $updatedPayment['iban'] );
		$this->assertNull( $updatedPayment['bic'] );
		$this->assertSame( 0, $updatedPayment['is_cancelled'] );
	}

	public function testStoreCancelledDirectDebitPayment(): void {
		$payment = DirectDebitPayment::create( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly, new Iban( self::IBAN ), self::BIC );
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$payment->cancel();
		$repo->storePayment( $payment );

		$updatedPayment = $this->fetchRawDirectDebitPaymentData();
		$this->assertSame( 1, $updatedPayment['is_cancelled'] );
	}

	public function testFindDirectDebitPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawDirectDebitPaymentData();

		$payment = $repo->getPaymentById( 1 );

		$this->assertInstanceOf( DirectDebitPayment::class, $payment );
		$paymentSpy = new DirectDebitPaymentInspector( $payment );
		$this->assertSame( 4223, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $paymentSpy->getInterval() );
		$this->assertSame( self::IBAN, $paymentSpy->getIban()?->toString() );
		$this->assertSame( self::BIC, $paymentSpy->getBic() );
		$this->assertFalse( $paymentSpy->getIsCancelled() );
	}

	public function testFindCancelledDirectDebitPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawDirectDebitPaymentData( [ 'is_cancelled' => true ] );

		$payment = $repo->getPaymentById( 1 );

		$this->assertInstanceOf( DirectDebitPayment::class, $payment );
		$paymentSpy = new DirectDebitPaymentInspector( $payment );
		$this->assertSame( 4223, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $paymentSpy->getInterval() );
		$this->assertSame( self::IBAN, $paymentSpy->getIban()?->toString() );
		$this->assertSame( self::BIC, $paymentSpy->getBic() );
		$this->assertTrue( $paymentSpy->getIsCancelled() );
	}

	public function testFindAnonymisedDirectDebitPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawAnonymisedDirectDebitPaymentData();

		$payment = $repo->getPaymentById( 1 );

		$this->assertInstanceOf( DirectDebitPayment::class, $payment );
		$paymentSpy = new DirectDebitPaymentInspector( $payment );
		$this->assertSame( 4223, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $paymentSpy->getInterval() );
		$this->assertNull( $paymentSpy->getBic() );
		$this->assertNull( $paymentSpy->getIban()?->toString() );
	}

	public function testStoreBankTransferPayment(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromInt( 99 ),
			PaymentInterval::Quarterly,
			new PaymentReferenceCode( 'XW', 'RAARR4', 'X' )
		);

		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawBankTransferPaymentData();
		$this->assertSame( 9900, $insertedPayment['amount'] );
		$this->assertSame( 3, $insertedPayment['payment_interval'] );
		$this->assertSame( 'UEB', $insertedPayment['payment_method'] );
		$this->assertSame( 'XW-RAA-RR4-X', $insertedPayment['payment_reference_code'] );
	}

	public function testFindBankTransferPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawBankTransferData();

		$payment = $repo->getPaymentById( 1 );
		$this->assertInstanceOf( BankTransferPayment::class, $payment );

		$paymentSpy = new BankTransferPaymentInspector( $payment );
		$this->assertSame( 4223, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $paymentSpy->getInterval() );
		$this->assertNotNull( $paymentSpy->getPaymentReferenceCode() );
		$this->assertSame( 'XW-RAA-RR4-Y', $paymentSpy->getPaymentReferenceCode()->getFormattedCode() );
	}

	public function testFindAnonymisedBankTransferPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawAnonymisedBankTransferData();

		$payment = $repo->getPaymentById( 1 );
		$this->assertInstanceOf( BankTransferPayment::class, $payment );

		$paymentSpy = new BankTransferPaymentInspector( $payment );
		$this->assertNull( $paymentSpy->getPaymentReferenceCode() );
	}

	public function testStoreSofortPayment(): void {
		$payment = SofortPayment::create(
			42,
			Euro::newFromInt( 12 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
		$payment->bookPayment( [ 'transactionId' => 'imatransID42', 'valuationDate' => '2021-06-24T23:00:00Z' ] );
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );
		$insertedPayment = $this->fetchRawSofortPaymentData();

		$this->assertSame( 1200, $insertedPayment['amount'] );
		$this->assertSame( 0, $insertedPayment['payment_interval'] );
		$this->assertSame( 'SUB', $insertedPayment['payment_method'] );
		$this->assertNotNull( $insertedPayment['valuation_date'] );
		$this->assertSame( 'imatransID42', $insertedPayment['transaction_id'] );
		$this->assertSame( 'XW-TAR-ARA-X', $insertedPayment['payment_reference_code'] );
	}

	public function testFindSofortPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawSofortData();

		$payment = $repo->getPaymentById( 42 );
		$this->assertInstanceOf( SofortPayment::class, $payment );
		$paymentSpy = new SofortPaymentInspector( $payment );
		$this->assertSame( 1233, $paymentSpy->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::OneTime, $paymentSpy->getInterval() );
		$this->assertEquals( new \DateTimeImmutable( '2021-06-24T23:00:00Z' ), $paymentSpy->getValuationDate() );
		$this->assertSame( 'imatransID42', $paymentSpy->getTransactionId() );
		$this->assertNotNull( $paymentSpy->getPaymentReferenceCode() );
		$this->assertSame( 'XW-TAR-ARA-Y', $paymentSpy->getPaymentReferenceCode()->getFormattedCode() );
	}

	public function testFindAnonymisedSofortPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawAnonymisedSofortData();

		$payment = $repo->getPaymentById( 42 );
		$this->assertInstanceOf( SofortPayment::class, $payment );
		$paymentSpy = new SofortPaymentInspector( $payment );
		$this->assertNull( $paymentSpy->getPaymentReferenceCode() );
	}

	public function testFindPaymentThrowsExceptionWhenPaymentIsNotFound(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$this->expectException( PaymentNotFoundException::class );

		$repo->getPaymentById( 999 );
	}

	public function testFindAndSaveRoundTrip(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawUnBookedCreditCardData();

		$payment = $repo->getPaymentById( 1 );
		// This is a dummy assertion to make PHPUnit and PHPStan happy,
		// the real test is that we avoid the PaymentOverrideException when storing again
		$this->assertInstanceOf( CreditCardPayment::class, $payment );

		$payment->bookPayment( [ 'transactionId' => 'badcaffee' ] );
		$repo->storePayment( $payment );
	}

	/**
	 * @return array<string,mixed>
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function fetchRawCreditCardPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'pcc.valuation_date', 'pcc.booking_data' )
			->from( 'payments', 'p' )
			->join( 'p', 'payments_credit_card', 'pcc', 'p.id=pcc.id' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Credit Card payment was not found!" );
		}
		return $data;
	}

	private function insertRawBookedCreditCardData(): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'MCP' ] );
		$this->connection->insert( 'payments_credit_card', [ 'id' => 1, 'valuation_date' => '2021-12-24 23:00:00', 'booking_data' => '{"transactionId":"1eetcaffee"}' ] );
	}

	private function insertRawUnBookedCreditCardData(): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'MCP' ] );
		$this->connection->insert( 'payments_credit_card', [ 'id' => 1, 'valuation_date' => null, 'booking_data' => null ] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function fetchRawPayPalPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'ppp.valuation_date', 'ppp.booking_data' )
			->from( 'payments', 'p' )
			->join( 'p', 'payments_paypal', 'ppp', 'p.id=ppp.id' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected PayPal payment was not found!" );
		}
		return $data;
	}

	private function insertRawPayPalData(): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'PPL' ] );
		$this->connection->insert( 'payments_paypal', [
			'id' => 1,
			'valuation_date' => '2021-12-24 23:00:00',
			'booking_data' => PayPalPaymentBookingData::newEncodedValidBookingData()
		] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function fetchRawBankTransferPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'pbt.payment_reference_code' )
			->from( 'payments', 'p' )
			->join( 'p', 'payments_bank_transfer', 'pbt', 'p.id=pbt.id' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Bank Transfer payment was not found!" );
		}
		return $data;
	}

	private function insertRawBankTransferData(): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'UEB' ] );
		$this->connection->insert( 'payments_bank_transfer', [ 'id' => 1, 'payment_reference_code' => 'XW-RAA-RR4-Y' ] );
	}

	private function insertRawAnonymisedBankTransferData(): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'UEB' ] );
		$this->connection->insert( 'payments_bank_transfer', [ 'id' => 1, 'payment_reference_code' => null ] );
	}

	/**
	 * @param array<string,mixed> $extraData
	 *
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function insertRawDirectDebitPaymentData( array $extraData = [] ): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'BEZ' ] );
		$this->connection->insert( 'payments_direct_debit', array_merge(
			[ 'id' => 1, 'iban' => self::IBAN, 'bic' => self::BIC, 'is_cancelled' => false ],
			$extraData
		) );
	}

	private function insertRawAnonymisedDirectDebitPaymentData(): void {
		$this->connection->insert( 'payments', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'BEZ' ] );
		$this->connection->insert( 'payments_direct_debit', [ 'id' => 1, 'iban' => null, 'bic' => null, 'is_cancelled' => false ] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function fetchRawDirectDebitPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'pdd.iban', 'pdd.bic', 'pdd.is_cancelled' )
			->from( 'payments', 'p' )
			->join( 'p', 'payments_direct_debit', 'pdd', 'p.id=pdd.id' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Direct Debit payment was not found!" );
		}
		return $data;
	}

	/**
	 * @return array<string,mixed>
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function fetchRawSofortPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select(
				'p.amount',
				'p.payment_interval',
				'p.payment_method',
				'psub.valuation_date',
				'psub.transaction_id',
				'psub.payment_reference_code'
			)
			->from( 'payments', 'p' )
			->join( 'p', 'payments_sofort', 'psub', 'p.id=psub.id' )
			->where( 'p.id=42' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Sofort payment was not found!" );
		}
		return $data;
	}

	private function insertRawSofortData(): void {
		$this->connection->insert( 'payments', [
			'id' => 42,
			'amount' => '1233',
			'payment_interval' => 0,
			'payment_method' => 'SUB'
		] );
		$this->connection->insert( 'payments_sofort', [
			'id' => 42,
			'valuation_date' => '2021-06-24T23:00:00Z',
			'transaction_id' => 'imatransID42',
			'payment_reference_code' => 'XW-TAR-ARA-Y'
		] );
	}

	private function insertRawAnonymisedSofortData(): void {
		$this->connection->insert( 'payments', [
			'id' => 42,
			'amount' => '1233',
			'payment_interval' => 0,
			'payment_method' => 'SUB'
		] );
		$this->connection->insert( 'payments_sofort', [
			'id' => 42,
			'valuation_date' => '2021-06-24 23:00:00',
			'transaction_id' => 'imatransID42',
			'payment_reference_code' => null
		] );
	}
}
