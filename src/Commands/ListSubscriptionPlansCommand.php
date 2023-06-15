<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

#[AsCommand(
	name: 'app:list-subscription-plans',
	description: 'Lists existing PayPal subscription plans.',
	hidden: false,
)]
class ListSubscriptionPlansCommand extends Command
{

	protected function execute(InputInterface $input, OutputInterface $output): int {

		// initialize API with environment parameters (Paypal url, client id and secret)
		// call listProducts on API
		// output product name and id
		// iterate over products, call getSubscriptionPlansForProduct on each product
		// iterate over plans, output each plan id, name and monthly interval (slightly indented)

		$url = getenv("PAYPAL_URL");
		print_r($_ENV);
		$output->writeln("Url is $url");

		return Command::SUCCESS;
	}

}
