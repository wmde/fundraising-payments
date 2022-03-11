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
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\CreditCardPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\PayPalPaymentInspector;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrinePaymentRepository
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\Euro
 * @covers \WMDE\Fundraising\PaymentContext\DataAccess\DoctrineTypes\PaymentInterval
 */
class DoctrinePaymentRepositoryTest extends TestCase {
	private Connection $connection;
	private EntityManager $entityManager;

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
		$this->connection->insert( 'payments_paypal', [ 'id' => 1, 'valuation_date' => '2021-12-24 23:00:00', 'booking_data' => PayPalPaymentBookingData::newEncodedValidBookingData() ] );
	}
}
