<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Commands;

use GuzzleHttp\Client;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
		// initialize API with environment parameters (Paypal url, client id and secret)
		$api = new GuzzlePaypalAPI(
			new Client( [ 'base_uri' => $_ENV['PAYPAL_URL'] ] ),
			$_ENV['PAYPAL_API_CLIENT_ID'],
			$_ENV['PAYPAL_API_CLIENT_SECRET'],
			new NullLogger()
		);

		// call listProducts on API
		$products = $api->listProducts();

		// output product name and id

		foreach ( $products as $product ) {
			print_r( $product );
		}

		// iterate over products, call getSubscriptionPlansForProduct on each product
		// iterate over plans, output each plan id, name and monthly interval (slightly indented)

		return Command::SUCCESS;
	}

}
