<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Terminal42\ContaoBynder\Api;
use Terminal42\ContaoBynder\EventListener\StoreDbafsMetadataEventListener;
use Terminal42\ContaoBynder\ImageHandler;

class Terminal42ContaoBynderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.php');

        // Configure API settings
        $settings = [
            'domain' => $config['domain'],
            'token' => $config['token'],
        ];
        $api = $container->findDefinition(Api::class);
        $api->setArgument(0, $settings);

        // Configure image handler
        $imageHandler = $container->findDefinition(ImageHandler::class);
        $imageHandler->setArgument(3, $config['derivativeName']);
        $imageHandler->setArgument(4, $config['derivativeOptions']);
        $imageHandler->setArgument(5, $config['targetDir']);

        // Meta data import
        $metadataEventListener = $container->findDefinition(StoreDbafsMetadataEventListener::class);
        $metadataEventListener->setArgument(3, $config['metaImportMapper']);
    }
}
