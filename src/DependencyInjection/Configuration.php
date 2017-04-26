<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('terminal42_contao_bynder');
        $rootNode
            ->children()
                ->scalarNode('consumerKey')
                    ->info('You can get the consumer key as described on https://developer-docs.bynder.com/API/Authorization-and-authentication/#create-a-consumer.')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('consumerSecret')
                    ->info('You can get the consumer secret as described on https://developer-docs.bynder.com/API/Authorization-and-authentication/#create-a-consumer.')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('token')
                    ->info('You can get the token as described on https://developer-docs.bynder.com/API/Authorization-and-authentication/#create-a-consumer.')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('tokenSecret')
                    ->info('You can get the token secret as described on https://developer-docs.bynder.com/API/Authorization-and-authentication/#create-a-consumer.')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('baseUrl')
                    ->info('The base url of your Bynder account (including the protocol).')
                    ->example('https://foobar.getbynder.com')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('targetDir')
                    ->info('The target directory the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path.')
                    ->example('bynder_assets (by default will turn into files/bynder_assets')
                    ->cannotBeEmpty()
                    ->defaultValue('bynder_assets')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
