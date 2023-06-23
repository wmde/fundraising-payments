<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class PayPalURLGeneratorConfigSchema implements ConfigurationInterface {
	public function getConfigTreeBuilder(): TreeBuilder {
		$treeBuilder = new TreeBuilder( 'paypal_api' );
		$treeBuilder->getRootNode()
			->arrayPrototype()
				->arrayPrototype()
					->children()
						->scalarNode( 'product_id' )->end()
						->scalarNode( 'product_name' )->end()
						->scalarNode( 'return_url' )->end()
						->scalarNode( 'cancel_url' )->end()
						->arrayNode( 'plans' )
							->arrayPrototype()
								->children()
									->scalarNode( 'id' )->end()
									->scalarNode( 'name' )->end()
									->scalarNode( 'interval' )->end()
								->end()
							->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}

}
