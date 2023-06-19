<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Commands;

use GuzzleHttp\Client;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\ScalarTypeConverter;
use WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanForProductUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\CreateSubscriptionPlanRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\ErrorResult;
use WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct\SuccessResult;

#[AsCommand(
	name: 'app:create-subscription-plan',
	description: 'Create subscription plan for recurring payments with PayPal.',
	hidden: false,
)]
class CreateSubscriptionPlansCommand extends Command {

	private const ALLOWED_INTERVALS = [
		'monthly' => PaymentInterval::Monthly,
		'quarterly' => PaymentInterval::Quarterly,
		'half-yearly' => PaymentInterval::HalfYearly,
		'yearly' => PaymentInterval::Yearly,
	];

	protected function configure(): void {
		$intervalNames = array_keys( self::ALLOWED_INTERVALS );

		$this->addArgument(
			'productId',
			InputArgument::REQUIRED,
			'Id of the Product. Ex.: Donation-1 or Membership-1'
		)->addArgument(
			'productName',
			InputArgument::REQUIRED,
			'Name of the Product'
		)->addArgument(
			'interval',
			InputArgument::REQUIRED,
			'Payment interval name for a product. Allowed values: ' . implode( ', ', $intervalNames )
		);
	}

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

		$intervalName = strtolower(
			ScalarTypeConverter::toString( $input->getArgument( 'interval' ) )
		);
		if ( !isset( self::ALLOWED_INTERVALS[$intervalName] ) ) {
			$output->writeln( "$intervalName is not an allowed interval name" );
			return Command::INVALID;
		}

		$useCase = new CreateSubscriptionPlanForProductUseCase( $api );
		$result = $useCase->create( new CreateSubscriptionPlanRequest(
			ScalarTypeConverter::toString( $input->getArgument( 'productId' ) ),
			ScalarTypeConverter::toString( $input->getArgument( 'productName' ) ),
			self::ALLOWED_INTERVALS[$intervalName]
		) );
		if ( $result instanceof ErrorResult ) {
			$output->writeln( $result->message );
			return Command::FAILURE;
		}
		$output->writeln( $this->conditionalOutput( $result ) );
		return Command::SUCCESS;
	}

	private function conditionalOutput( SuccessResult $result ): string {
		$alreadyExistedString = "already exists";
		$wasCreatedString = "was created";
		$productString = sprintf(
			'The product %s with ID "%s" %s.',
			$result->successfullyCreatedProduct->name,
			$result->successfullyCreatedProduct->id,
			$result->productAlreadyExisted ? $alreadyExistedString : $wasCreatedString
		);

		$subscriptionPlanString = sprintf(
			'The %s subscription plan with ID "%s" for product ID "%s" %s.',
			strtolower( $result->successfullyCreatedSubscriptionPlan->monthlyInterval->name ),
			$result->successfullyCreatedSubscriptionPlan->id,
			$result->successfullyCreatedSubscriptionPlan->productId,
			$result->subscriptionPlanAlreadyExisted ? $alreadyExistedString : $wasCreatedString
		);
		return $productString . "\n" . $subscriptionPlanString;
	}

}
