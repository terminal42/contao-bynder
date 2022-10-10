<?php

declare(strict_types=1);

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
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
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('terminal42_contao_bynder');

        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('domain')
            ->info('The domain of your Bynder account (without the protocol).')
            ->example('foobar.getbynder.com')
            ->cannotBeEmpty()
            ->isRequired()
            ->end()
            ->scalarNode('token')
            ->info('You can get the permanent token as described on https://support.bynder.com/hc/en-us/articles/360013875300-Permanent-Tokens.')
            ->cannotBeEmpty()
            ->isRequired()
            ->end()
            ->scalarNode('targetDir')
            ->info('The target directory the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path.')
            ->example('bynder_assets (by default will turn into files/bynder_assets')
            ->cannotBeEmpty()
            ->defaultValue('bynder_assets')
            ->end()
            ->scalarNode('derivativeName')
            ->info('The derivativeName contains the derivative you added in Bynder. It will be used to fetch a derivative of the original when downloading it to your Contao installation.')
            ->example('contao_derivative')
            ->cannotBeEmpty()
            ->isRequired()
            ->end()
            ->variableNode('derivativeOptions')
            ->info('The derivativeOptions contains an array of parameters (key -> value) that are added to the derivative URL when fetching the derivative. Note that this only works for on-the-fly derivatives.')
            ->treatNullLike([])
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
