<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\ScalarTypeConverter;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalPaymentProviderAdapterConfigReader;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanForProductUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\ErrorResult;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\SuccessResult;

#[AsCommand(
	name: 'app:create-subscription-plans',
	description: 'Create subscription plan for recurring payments with PayPal.',
	hidden: false,
)]
class CreateSubscriptionPlansCommand extends Command {

	private const ALREADY_EXISTS_SNIPPET = 'already exists';
	private const WAS_CREATED_SNIPPET = 'was created';

	public function __construct(
		private readonly PaypalAPI $paypalAPI
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->addArgument(
			'configFile',
			InputArgument::REQUIRED,
			'File name of PayPal subscription plan configuration'
		);
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$useCase = new CreateSubscriptionPlanForProductUseCase( $this->paypalAPI );

		$configuration = PayPalPaymentProviderAdapterConfigReader::readConfig(
			ScalarTypeConverter::toString( $input->getArgument( 'configFile' ) )
		);

		foreach ( $configuration as $productConfiguration ) {
			foreach ( $productConfiguration as $languageSpecificConfiguration ) {
				foreach ( $languageSpecificConfiguration['subscription_plans'] as $idx => $planConfiguration ) {
					$intervalName = ScalarTypeConverter::toString( $planConfiguration['interval'] );
					try {
						$parsedInterval = PaymentInterval::fromString( $intervalName );
					} catch ( \OutOfBoundsException ) {
						$output->writeln( "$intervalName is not an allowed interval name" );
						return Command::INVALID;
					}
					$result = $useCase->create( new CreateSubscriptionPlanRequest(
						$languageSpecificConfiguration['product_id'],
						$languageSpecificConfiguration['product_name'],
						$parsedInterval,
						$planConfiguration['name']
					) );

					if ( $result instanceof ErrorResult ) {
						$output->writeln( $result->message );
						return Command::FAILURE;
					}
					if ( $idx === 0 ) {
						$output->writeln( $this->formattedOutputForProduct( $result ) );
					}
					$output->writeln( $this->formattedOutputForSubscriptionPlan( $result ) );
				}
			}
		}
		return Command::SUCCESS;
	}

	private function formattedOutputForProduct( SuccessResult $result ): string {
		return sprintf(
			"Product '%s' (ID %s) %s",
			$result->successfullyCreatedProduct->name,
			$result->successfullyCreatedProduct->id,
			$result->productAlreadyExisted ? self::ALREADY_EXISTS_SNIPPET : self::WAS_CREATED_SNIPPET
		);
	}

	private function formattedOutputForSubscriptionPlan( SuccessResult $result ): string {
		return sprintf(
			'    The %s subscription plan "%s" (ID "%s") %s.',
			strtolower( $result->successfullyCreatedSubscriptionPlan->monthlyInterval->name ),
			$result->successfullyCreatedSubscriptionPlan->name,
			$result->successfullyCreatedSubscriptionPlan->id,
			$result->subscriptionPlanAlreadyExisted ? self::ALREADY_EXISTS_SNIPPET : self::WAS_CREATED_SNIPPET
		);
	}
}
