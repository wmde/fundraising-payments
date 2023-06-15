<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:list-subscription-plans',
	description: 'Lists existing PayPal subscription plans.',
	hidden: false,
)]
class ListSubscriptionPlansCommand extends Command
{
	protected string $name = 'app:list-subscription-plans';




	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln("Hello world");
		return Command::SUCCESS;
	}

}
