<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Integration\Services\TransactionIdFinder;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder\DoctrineTransactionIdFinder;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SequentialPaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\TransactionIdFinder\DoctrineTransactionIdFinder
 */
class DoctrineTransactionIdFinderTest extends TestCase {
	private Connection $connection;
	private EntityManager $entityManager;

	protected function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
		$this->connection = $this->entityManager->getConnection();
	}

	/**
	 * @dataProvider provideNonPayPalPayments
	 */
	public function testGivenNonPayPalPayment_itReturnsEmptyArray( Payment $payment ): void {
		$finder = new DoctrineTransactionIdFinder( $this->connection );

		$this->assertSame( [], $finder->getAllTransactionIDs( $payment ) );
	}

	/**
	 * @return iterable<array{Payment}>
	 */
	public function provideNonPayPalPayments(): iterable {
		$paymentReferenceCode = PaymentReferenceCode::newFromString( 'XW-DAR-E99-T' );
		yield "credit card" => [ new CreditCardPayment( 1, Euro::newFromCents( 1234 ), PaymentInterval::Monthly ) ];
		yield "sofort" => [ SofortPayment::create( 1, Euro::newFromCents( 1234 ), PaymentInterval::OneTime, $paymentReferenceCode ) ];
		yield "bank transfer" => [ BankTransferPayment::create( 1, Euro::newFromCents( 1234 ), PaymentInterval::Monthly, $paymentReferenceCode ) ];
		yield "direct debit" => [ DirectDebitPayment::create( 1, Euro::newFromCents( 1234 ), PaymentInterval::Monthly, new Iban( 'DE12500105170648489890' ), '' ) ];
	}

	public function testGivenUnbookedPayPalPayment_itReturnsEmptyArray(): void {
		$finder = new DoctrineTransactionIdFinder( $this->connection );
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1234 ), PaymentInterval::Monthly );

		$this->assertSame( [], $finder->getAllTransactionIDs( $payment ) );
	}

	public function testGivenBookedOneTimePayPalPayment_itReturnsTransactionId(): void {
		$finder = new DoctrineTransactionIdFinder( $this->connection );
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1234 ), PaymentInterval::OneTime );
		$payment->bookPayment( [ 'payer_id' => 'ABCD1', 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE, 'txn_id' => PayPalPaymentBookingData::TRANSACTION_ID ], new DummyPaymentIdRepository() );

		$this->assertSame( [ PayPalPaymentBookingData::TRANSACTION_ID => 1 ], $finder->getAllTransactionIDs( $payment ) );
	}

	public function testGivenBookedPayPalPaymentWithBookedFollowUps_itReturnsAllTransactionIds(): void {
		$finder = new DoctrineTransactionIdFinder( $this->connection );
		$expectedTransactionIds = [
			'ID_ONE' => 1,
			'ID_TWO' => 55,
			'ID_THREE' => 56,
			'ID_FOUR' => 57,
		];
		[ $initialPayment, $childPayment, ] = $this->givenPaymentsWithFollowups();

		$this->assertSame( $expectedTransactionIds, $finder->getAllTransactionIDs( $initialPayment ) );
		$this->assertSame( $expectedTransactionIds, $finder->getAllTransactionIDs( $childPayment ) );
	}

	public function testGivenPayments_transactionIdExistsReturnsTrue(): void {
		$finder = new DoctrineTransactionIdFinder( $this->connection );
		$this->givenPaymentsWithFollowups();

		$this->assertTrue( $finder->transactionIdExists( "ID_ONE" ) );
		$this->assertTrue( $finder->transactionIdExists( "ID_TWO" ) );
		$this->assertFalse( $finder->transactionIdExists( "MYSTERY ID 23" ) );
	}

	/**
	 * @return PayPalPayment[]
	 */
	private function givenPaymentsWithFollowups(): array {
		$idRepository = new SequentialPaymentIDRepository( 55 );
		$initialPayment = new PayPalPayment( 1, Euro::newFromCents( 1234 ), PaymentInterval::Monthly );
		$initialPayment->bookPayment( [ 'payer_id' => 'ABCD1', 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE, 'txn_id' => 'ID_ONE' ], $idRepository );
		$child1 = $initialPayment->bookPayment( [ 'payer_id' => 'ABCD1', 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE, 'txn_id' => 'ID_TWO' ], $idRepository );
		$child2 = $initialPayment->bookPayment( [ 'payer_id' => 'ABCD1', 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE, 'txn_id' => 'ID_THREE' ], $idRepository );
		$child3 = $initialPayment->bookPayment( [ 'payer_id' => 'ABCD1', 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE, 'txn_id' => 'ID_FOUR' ], $idRepository );
		$this->entityManager->persist( $initialPayment );
		$this->entityManager->persist( $child1 );
		$this->entityManager->persist( $child2 );
		$this->entityManager->persist( $child3 );
		$this->entityManager->flush();
		return [ $initialPayment, $child1, $child2, $child3 ];
	}

}
