<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Terminal42ContaoBynderExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');

        // Configure API settings
        $settings = [
            'consumerKey' => $config['consumerKey'],
            'consumerSecret' => $config['consumerSecret'],
            'token' => $config['token'],
            'tokenSecret' => $config['tokenSecret'],
            'baseUrl' => $config['baseUrl'],
        ];
        $api = $container->findDefinition('terminal42.contao_bynder.api');
        $api->setArgument(0, $settings);

        // Configure target dir on image handler
        $imageHandler = $container->findDefinition('terminal42.contao_bynder.image_handler');
        $imageHandler->setArgument(2, $config['targetDir']);
    }
}
