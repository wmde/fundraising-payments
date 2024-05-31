<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\KontoCheck;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\KontoCheck\KontoCheckLibraryInitializationException;

#[CoversClass( KontoCheckLibraryInitializationException::class )]
class KontoCheckLibraryInitializationExceptionTest extends TestCase {
	public function testGivenNoCode_ExceptionHasDefaultMessageAndCode(): void {
		$ex = new KontoCheckLibraryInitializationException();

		$this->assertSame( 'Could not initialize library with bank data file.', $ex->getMessage() );
		$this->assertSame( 0, $ex->getCode() );
	}

	public function testGivenCode_ExceptionAddsReasonToMessage(): void {
		$ex = new KontoCheckLibraryInitializationException( -13 );

		// Yes, the kontocheck PHP extension returns German error messages
		$this->assertSame( 'Could not initialize library with bank data file. Reason: schwerer Fehler im Konto_check-Modul', $ex->getMessage() );
		$this->assertSame( -13, $ex->getCode() );
	}
}
