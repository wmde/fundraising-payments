<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentRepository;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Exception\PaymentOverrideException;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalOrder;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalSubscription;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

#[CoversClass( DoctrinePaymentRepository::class )]
#[CoversClass( \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Euro::class )]
#[CoversClass( \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\PaymentInterval::class )]
#[CoversClass( \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Iban::class )]
class DoctrinePaymentRepositoryTest extends TestCase {

	private Connection $connection;
	private EntityManager $entityManager;
	private const IBAN = 'DE00123456789012345678';
	private const BIC = 'SCROUSDBXXX';
	private const FOLLOWUP_PAYMENT_ID = 2;
	private const VALUATION_DATE = '2021-12-24 23:00:00';

	protected function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
		$this->connection = $this->entityManager->getConnection();
	}

	public function testStoreCreditCardPayment(): void {
		$payment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$payment->bookPayment( [ 'transactionId' => 'badcaffee', 'amount' => 9900 ], new DummyPaymentIdRepository() );
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawCreditCardPaymentData();
		$this->assertSame( 9900, $insertedPayment['amount'] );
		$this->assertSame( 3, $insertedPayment['payment_interval'] );
		$this->assertSame( 'MCP', $insertedPayment['payment_method'] );
		$this->assertNotNull( $insertedPayment['valuation_date'] );
		$this->assertSame( '{"transactionId":"badcaffee","amount":"9900"}', $insertedPayment['booking_data'] );
	}

	public function testRepositoryCatchesORMErrorsFromPaymentsWithTheSameId(): void {
		$firstPayment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$firstPayment->bookPayment( [ 'transactionId' => 'badcaffee', 'amount' => 9900 ], new DummyPaymentIdRepository() );
		$secondPayment = new CreditCardPayment( 1, Euro::newFromInt( 42 ), PaymentInterval::Monthly );
		$this->entityManager->getConfiguration()->setRejectIdCollisionInIdentityMap( true );
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$repo->storePayment( $firstPayment );

		$this->expectException( PaymentOverrideException::class );

		$repo->storePayment( $secondPayment );
	}

	public function testRepositoryCatchesDatabaseErrorsFromPaymentsWithTheSameId(): void {
		$firstPayment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$firstPayment->bookPayment( [ 'transactionId' => 'badcaffee', 'amount' => 9900 ], new DummyPaymentIdRepository() );
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
		$this->assertSame( 4223, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $payment->getInterval() );
		$this->assertEquals( new \DateTimeImmutable( self::VALUATION_DATE ), $payment->getValuationDate() );
		$displayValues = $payment->getDisplayValues();
		$this->assertSame( '1eetcaffee', $displayValues['ext_payment_id'] );
	}

	public function testStorePayPalPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$bookingData = PayPalPaymentBookingData::newValidBookingData();

		$payment->bookPayment( $bookingData, new DummyPaymentIdRepository() );
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawPayPalPaymentData();
		$this->assertSame( 9900, $insertedPayment['amount'] );
		$this->assertSame( 3, $insertedPayment['payment_interval'] );
		$this->assertSame( 'PPL', $insertedPayment['payment_method'] );
		$this->assertSame( '2012-12-02 18:54:49', $insertedPayment['valuation_date'] );
		$this->assertNull( $insertedPayment['parent_payment_id'] );
		$this->assertSame( PayPalPaymentBookingData::TRANSACTION_ID, $insertedPayment['transaction_id'] );
		$this->assertSame( PayPalPaymentBookingData::newEncodedValidBookingData(), $insertedPayment['booking_data'] );
	}

	public function testStoreFollowupPayPalPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );
		$bookingData = PayPalPaymentBookingData::newValidBookingData();
		$payment->bookPayment( $bookingData, new DummyPaymentIdRepository() );
		$followupPayment = $payment->bookPayment( $bookingData, $this->makeIdGeneratorForFollowupPayments() );
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );
		$repo->storePayment( $followupPayment );

		$insertedPayment = $this->fetchRawPayPalPaymentData( self::FOLLOWUP_PAYMENT_ID );
		$this->assertSame( 1, $insertedPayment['parent_payment_id'] );
	}

	public function testFindPayPalPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawPayPalData();

		$payment = $repo->getPaymentById( 1 );
		$followupPayment = $repo->getPaymentById( 2 );
		$this->assertInstanceOf( PayPalPayment::class, $payment );

		$this->assertSame( 4223, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $payment->getInterval() );
		$this->assertEquals( new \DateTimeImmutable( self::VALUATION_DATE ), $payment->getValuationDate() );
		$this->assertSame( [
			'amount' => 4223,
			'interval' => 12,
			'paymentType' => 'PPL',
			'paypal_payer_id' => '42DFPNJDF8RED',
			'paypal_subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'paypal_payer_status' => 'verified',
			'paypal_mc_gross' => '2.70',
			'paypal_mc_currency' => 'EUR',
			'paypal_mc_fee' => '2.70',
			'paypal_settle_amount' => '2.70',
			'ext_payment_id' => 'T4242',
			'ext_subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'ext_payment_type' => 'instant',
			'ext_payment_status' => 'processed/express_checkout',
			'ext_payment_account' => '42DFPNJDF8RED',
			'ext_payment_timestamp' => '10:54:49 Dec 02, 2012 PST',
		], $payment->getDisplayValues() );

		$this->assertSame( 1, $followupPayment->getLegacyData()->paymentSpecificValues['parent_payment_id'] );
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
		$this->assertSame( 4223, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $payment->getInterval() );
		$displayValues = $payment->getDisplayValues();
		$this->assertSame( self::IBAN, $displayValues['iban'] );
		$this->assertSame( self::BIC, $displayValues['bic'] );
		$this->assertFalse( $payment->isCancelled() );
	}

	public function testFindCancelledDirectDebitPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawDirectDebitPaymentData( [ 'is_cancelled' => true ] );

		$payment = $repo->getPaymentById( 1 );

		$this->assertInstanceOf( DirectDebitPayment::class, $payment );
		$this->assertSame( 4223, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $payment->getInterval() );
		$this->assertTrue( $payment->isCancelled() );
	}

	public function testFindAnonymisedDirectDebitPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawAnonymisedDirectDebitPaymentData();

		$payment = $repo->getPaymentById( 1 );

		$this->assertInstanceOf( DirectDebitPayment::class, $payment );
		$this->assertSame( 4223, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $payment->getInterval() );
		$displayValues = $payment->getDisplayValues();
		$this->assertSame( '', $displayValues['iban'] );
		$this->assertSame( '', $displayValues['bic'] );
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

		$this->assertSame( 4223, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::Yearly, $payment->getInterval() );
		$displayValues = $payment->getDisplayValues();
		$this->assertSame( 'XW-RAA-RR4-Y', $displayValues['paymentReferenceCode'] );
	}

	public function testFindAnonymisedBankTransferPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawAnonymisedBankTransferData();

		$payment = $repo->getPaymentById( 1 );
		$this->assertInstanceOf( BankTransferPayment::class, $payment );
		$displayValues = $payment->getDisplayValues();
		$this->assertArrayNotHasKey( 'paymentReferenceCode', $displayValues );
	}

	public function testOnAnonymiseBankTransferPayment_PaymentReferenceCodeIsKeptAndDetached(): void {
		$payment = BankTransferPayment::create(
			1,
			Euro::newFromInt( 99 ),
			PaymentInterval::Quarterly,
			new PaymentReferenceCode( 'XW', 'RAARR4', 'X' )
		);

		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );
		$payment->anonymise();
		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawBankTransferPaymentData();
		$keptPaymentReferenceCode = $this->fetchRawPaymentReferenceCode();

		$this->assertNull( $insertedPayment['payment_reference_code'] );
		$this->assertSame( 'XW-RAA-RR4-X', $keptPaymentReferenceCode['payment_reference_code'] );
	}

	public function testStoreSofortPayment(): void {
		$payment = SofortPayment::create(
			42,
			Euro::newFromInt( 12 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
		$payment->bookPayment(
			[ 'transactionId' => 'imatransID42', 'valuationDate' => '2021-06-24T23:00:00Z' ],
			new DummyPaymentIdRepository()
		);
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
		$this->assertSame( 1233, $payment->getAmount()->getEuroCents() );
		$this->assertSame( PaymentInterval::OneTime, $payment->getInterval() );
		$this->assertEquals( new \DateTimeImmutable( '2021-06-24T23:00:00Z' ), $payment->getValuationDate() );
		$displayValues = $payment->getDisplayValues();
		$this->assertSame( 'XW-TAR-ARA-Y', $displayValues['paymentReferenceCode'] );
	}

	public function testFindAnonymisedSofortPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$this->insertRawAnonymisedSofortData();

		$payment = $repo->getPaymentById( 42 );
		$this->assertInstanceOf( SofortPayment::class, $payment );
		$displayValues = $payment->getDisplayValues();
		$this->assertSame( '', $displayValues['paymentReferenceCode'] );
	}

	public function testOnAnonymiseSofortPayment_PaymentReferenceCodeIsKeptAndDetached(): void {
		$payment = SofortPayment::create(
			42,
			Euro::newFromInt( 12 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'TARARA', 'X' )
		);
		$payment->bookPayment(
			[ 'transactionId' => 'imatransID42', 'valuationDate' => '2021-06-24T23:00:00Z' ],
			new DummyPaymentIdRepository()
		);
		$repo = new DoctrinePaymentRepository( $this->entityManager );

		$repo->storePayment( $payment );
		$payment->anonymise();
		$repo->storePayment( $payment );

		$insertedPayment = $this->fetchRawSofortPaymentData();
		$keptPaymentReferenceCode = $this->fetchRawPaymentReferenceCode();

		$this->assertNull( $insertedPayment['payment_reference_code'] );
		$this->assertSame( 'XW-TAR-ARA-X', $keptPaymentReferenceCode['payment_reference_code'] );
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

		$payment->bookPayment( [ 'transactionId' => 'badcaffee', 'amount' => 4223 ], new DummyPaymentIdRepository() );
		$repo->storePayment( $payment );
	}

	public function testStorePayPalIdentifierForRecurringPayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$payment = new PayPalPayment( 5, Euro::newFromInt( 8 ), PaymentInterval::Yearly );
		$repo->storePayPalIdentifier( new PayPalSubscription( $payment, 'SID-1' ) );

		$identifier = $this->fetchRawPayPalIdentifier( 5 );
		$this->assertSame( 5, $identifier['payment_id'] );
		$this->assertSame( 'SID-1', $identifier['subscription_id'] );
		$this->assertSame( 'S', $identifier['identifier_type'] );
	}

	public function testStorePayPalIdentifierForOneTimePayment(): void {
		$repo = new DoctrinePaymentRepository( $this->entityManager );
		$payment = new PayPalPayment( 9, Euro::newFromInt( 87 ), PaymentInterval::OneTime );
		$repo->storePayPalIdentifier( new PayPalOrder( $payment, 'O-1234', 'TXN-9' ) );

		$identifier = $this->fetchRawPayPalIdentifier( 9 );
		$this->assertSame( 9, $identifier['payment_id'] );
		$this->assertSame( 'O-1234', $identifier['order_id'] );
		$this->assertSame( 'TXN-9', $identifier['transaction_id'] );
		$this->assertSame( 'O', $identifier['identifier_type'] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawCreditCardPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'pcc.valuation_date', 'pcc.booking_data' )
			->from( 'payment', 'p' )
			->join( 'p', 'payment_credit_card', 'pcc', 'p.id=pcc.id' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Credit Card payment was not found!" );
		}
		return $data;
	}

	private function insertRawBookedCreditCardData(): void {
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'MCP' ] );
		$this->connection->insert( 'payment_credit_card', [ 'id' => 1, 'valuation_date' => self::VALUATION_DATE, 'booking_data' => '{"transactionId":"1eetcaffee","amount":"42.23"}' ] );
	}

	private function insertRawUnBookedCreditCardData(): void {
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'MCP' ] );
		$this->connection->insert( 'payment_credit_card', [ 'id' => 1, 'valuation_date' => null, 'booking_data' => null ] );
	}

	/**
	 * @param int $paymentId
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawPayPalPaymentData( int $paymentId = 1 ): array {
		$data = $this->connection->createQueryBuilder()
			->select(
				'p.amount',
				'p.payment_interval',
				'p.payment_method',
				'ppp.valuation_date',
				'ppp.transaction_id',
				'ppp.booking_data',
				'ppp.parent_payment_id'
			)
			->from( 'payment', 'p' )
			->join( 'p', 'payment_paypal', 'ppp', 'p.id=ppp.id' )
			->where( 'p.id=:paymentId' )
			->setParameter( 'paymentId', $paymentId )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected PayPal payment was not found!" );
		}
		return $data;
	}

	private function insertRawPayPalData(): void {
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'PPL' ] );
		$this->connection->insert( 'payment', [ 'id' => 2, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'PPL' ] );
		$this->connection->insert( 'payment_paypal', [
			'id' => 1,
			'valuation_date' => self::VALUATION_DATE,
			'booking_data' => PayPalPaymentBookingData::newEncodedValidBookingData()
		] );
		$this->connection->insert( 'payment_paypal', [
			'id' => 2,
			'valuation_date' => '2022-12-24 23:00:00',
			'booking_data' => PayPalPaymentBookingData::newEncodedValidBookingData(),
			'parent_payment_id' => 1
		] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawBankTransferPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'prc.code AS payment_reference_code' )
			->from( 'payment', 'p' )
			->leftJoin( 'p', 'payment_bank_transfer', 'pbt', 'p.id=pbt.id' )
			->leftJoin( 'pbt', 'payment_reference_codes', 'prc', 'pbt.payment_reference_code=prc.code' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Bank Transfer payment was not found!" );
		}
		return $data;
	}

	private function insertRawBankTransferData(): void {
		$this->connection->insert( 'payment_reference_codes', [ 'code' => 'XW-RAA-RR4-Y' ] );
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'UEB' ] );
		$this->connection->insert( 'payment_bank_transfer', [ 'id' => 1, 'payment_reference_code' => 'XW-RAA-RR4-Y', 'is_cancelled' => 0 ] );
	}

	private function insertRawAnonymisedBankTransferData(): void {
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'UEB' ] );
		$this->connection->insert( 'payment_bank_transfer', [ 'id' => 1, 'payment_reference_code' => null, 'is_cancelled' => 0 ] );
	}

	/**
	 * @param array<string,mixed> $extraData
	 *
	 * @return void
	 * @throws Exception
	 */
	private function insertRawDirectDebitPaymentData( array $extraData = [] ): void {
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'BEZ' ] );
		$this->connection->insert( 'payment_direct_debit', array_merge(
			[ 'id' => 1, 'iban' => self::IBAN, 'bic' => self::BIC, 'is_cancelled' => 0 ],
			$extraData
		) );
	}

	private function insertRawAnonymisedDirectDebitPaymentData(): void {
		$this->connection->insert( 'payment', [ 'id' => 1, 'amount' => '4223', 'payment_interval' => 12, 'payment_method' => 'BEZ' ] );
		$this->connection->insert( 'payment_direct_debit', [ 'id' => 1, 'iban' => null, 'bic' => null, 'is_cancelled' => 0 ] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawDirectDebitPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.amount', 'p.payment_interval', 'p.payment_method', 'pdd.iban', 'pdd.bic', 'pdd.is_cancelled' )
			->from( 'payment', 'p' )
			->join( 'p', 'payment_direct_debit', 'pdd', 'p.id=pdd.id' )
			->where( 'p.id=1' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Direct Debit payment was not found!" );
		}
		return $data;
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawSofortPaymentData(): array {
		$data = $this->connection->createQueryBuilder()
			->select(
				'p.amount',
				'p.payment_interval',
				'p.payment_method',
				'psub.valuation_date',
				'psub.transaction_id',
				'prc.code AS payment_reference_code'
			)
			->from( 'payment', 'p' )
			->leftJoin( 'p', 'payment_sofort', 'psub', 'p.id=psub.id' )
			->leftJoin( 'psub', 'payment_reference_codes', 'prc', 'psub.payment_reference_code=prc.code' )
			->where( 'p.id=42' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Sofort payment was not found!" );
		}
		return $data;
	}

	private function insertRawSofortData(): void {
		$this->connection->insert( 'payment_reference_codes', [ 'code' => 'XW-TAR-ARA-Y' ] );
		$this->connection->insert( 'payment', [
			'id' => 42,
			'amount' => '1233',
			'payment_interval' => 0,
			'payment_method' => 'SUB'
		] );
		$this->connection->insert( 'payment_sofort', [
			'id' => 42,
			'valuation_date' => '2021-06-24 23:00:00',
			'transaction_id' => 'imatransID42',
			'payment_reference_code' => 'XW-TAR-ARA-Y'
		] );
	}

	private function insertRawAnonymisedSofortData(): void {
		$this->connection->insert( 'payment', [
			'id' => 42,
			'amount' => '1233',
			'payment_interval' => 0,
			'payment_method' => 'SUB'
		] );
		$this->connection->insert( 'payment_sofort', [
			'id' => 42,
			'valuation_date' => '2021-06-24 23:00:00',
			'transaction_id' => 'imatransID42',
			'payment_reference_code' => null
		] );
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawPaymentReferenceCode(): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.code as payment_reference_code' )
			->from( 'payment_reference_codes', 'p' )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Payment Reference Code was not found!" );
		}
		return $data;
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	private function fetchRawPayPalIdentifier( int $paymentId ): array {
		$data = $this->connection->createQueryBuilder()
			->select( 'p.subscription_id', 'p.order_id', 'p.transaction_id', 'p.payment_id', 'p.identifier_type' )
			->from( 'payment_paypal_identifier', 'p' )
			->where( 'p.payment_id = :payment_id' )
			->setParameter( 'payment_id', $paymentId, ParameterType::INTEGER )
			->fetchAssociative();
		if ( $data === false ) {
			throw new AssertionFailedError( "Expected Paypal ID was not found!" );
		}
		return $data;
	}

	private function makeIdGeneratorForFollowupPayments(): PaymentIdRepository {
		$idGeneratorStub = $this->createStub( PaymentIdRepository::class );
		$idGeneratorStub->method( 'getNewId' )->willReturn( self::FOLLOWUP_PAYMENT_ID );
		return $idGeneratorStub;
	}
}
