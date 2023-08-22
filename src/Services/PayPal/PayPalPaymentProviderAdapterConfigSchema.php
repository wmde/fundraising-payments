<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class PayPalPaymentProviderAdapterConfigSchema implements ConfigurationInterface {
	public function getConfigTreeBuilder(): TreeBuilder {
		$treeBuilder = new TreeBuilder( 'paypal_api' );
		$treeBuilder->getRootNode()
			->arrayPrototype()
				->requiresAtLeastOneElement()
				->arrayPrototype()
					->children()
						->scalarNode( 'product_id' )->isRequired()->end()
						->scalarNode( 'product_name' )->isRequired()->end()
						->scalarNode( 'return_url' )->isRequired()->end()
						->scalarNode( 'cancel_url' )->isRequired()->end()
						->arrayNode( 'subscription_plans' )
							->isRequired()
							->arrayPrototype()
								->children()
									->scalarNode( 'id' )->isRequired()->end()
									->scalarNode( 'name' )->isRequired()->end()
									->enumNode( 'interval' )
										->isRequired()
										->values( [
											PaymentInterval::Monthly->name,
											PaymentInterval::Quarterly->name,
											PaymentInterval::HalfYearly->name,
											PaymentInterval::Yearly->name
										] )
									->end()
								->end()
							->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}

}
