<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(
	name: 'app:list-subscription-plans',
	description: 'Lists existing PayPal subscription plans.',
	hidden: false,
)]
class ListSubscriptionPlansCommand extends Command {

	public function __construct(
		private readonly PaypalAPI $paypalAPI
	) {
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$products = $this->paypalAPI->listProducts();

		if ( count( $products ) === 0 ) {
			$output->writeln( 'No products and plans configured' );
			return Command::SUCCESS;
		}

		$table = new Table( $output );
		$table->setHeaders( [ 'Product ID', 'Subscription plan ID', 'Interval' ] );

		foreach ( $products as $product ) {
			foreach ( $this->paypalAPI->listSubscriptionPlansForProduct( $product->id ) as $subscriptionPlanForProduct ) {
				$table->addRow( [ $product->id, $subscriptionPlanForProduct->id, $subscriptionPlanForProduct->monthlyInterval->name ] );
			}
			$table->addRow( new TableSeparator() );

		}
		$table->render();
		return Command::SUCCESS;
	}
}
