<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Cli;

use WMDE\InspectorGenerator\InspectorGenerator;
use WMDE\InspectorGenerator\Psr4CodeWriter;

class InspectorGeneratorCommand {

	private const INSPECTOR_NAMESPACE = 'WMDE\Fundraising\PaymentContext\Tests\Inspectors';
	private const WRITER_NAMESPACE_BASE = 'WMDE\Fundraising\PaymentContext\Tests\\';
	private const INSPECTOR_FOLDER_LOCATION = __DIR__ . '/../';
	private const INSPECTORS = [
		'WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment' => 'CreditCardPaymentInspector',
		'WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment' => 'PayPalPaymentInspector',
		'WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment' => 'BankTransferPaymentInspector',
		'WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment' => 'SofortPaymentInspector',
	];

	public static function run(): void {
		$generator = new InspectorGenerator( self::INSPECTOR_NAMESPACE );
		$writer = new Psr4CodeWriter( [ self::WRITER_NAMESPACE_BASE => self::INSPECTOR_FOLDER_LOCATION ] );
		foreach ( self::INSPECTORS as $namespace => $classname ) {
			$writer->writeResult( $generator->generateInspector( $namespace, $classname ) );
		}
	}
}
