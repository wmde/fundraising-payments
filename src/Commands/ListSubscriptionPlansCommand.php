<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Commands;

use GuzzleHttp\Client;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI;

#[AsCommand(
	name: 'app:list-subscription-plans',
	description: 'Lists existing PayPal subscription plans.',
	hidden: false,
)]
class ListSubscriptionPlansCommand extends Command {

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$clientId = $_ENV['PAYPAL_API_CLIENT_ID'] ?? '';
		$secret = $_ENV['PAYPAL_API_CLIENT_SECRET'] ?? '';
		$baseUri = $_ENV['PAYPAL_API_URL'] ?? '';
		if ( !$clientId || !$secret || !$baseUri ) {
			$output->writeln( 'You must put PAYPAL_API_URL, PAYPAL_API_CLIENT_ID and PAYPAL_API_CLIENT_SECRET' );
			return Command::FAILURE;
		}

		$api = new GuzzlePaypalAPI(
			new Client( [ 'base_uri' => $baseUri ] ),
			$clientId,
			$secret,
			new NullLogger()
		);

		$products = $api->listProducts();

		if ( count( $products ) === 0 ) {
			$output->writeln( 'No products and plans configured' );
			return Command::SUCCESS;
		}

		$table = new Table( $output );
		$table->setHeaders( [ 'Product ID', 'Subscription plan ID', 'Interval' ] );

		foreach ( $products as $product ) {
			foreach ( $api->listSubscriptionPlansForProduct( $product->id ) as $subscriptionPlanForProduct ) {
				$table->addRow( [ $product->id, $subscriptionPlanForProduct->id, $subscriptionPlanForProduct->monthlyInterval->name ] );
			}
			$table->addRow( new TableSeparator() );

		}
		$table->render();
		return Command::SUCCESS;
	}
}
