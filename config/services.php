<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoBynder\Api;
use Terminal42\ContaoBynder\Controller\ApiController;
use Terminal42\ContaoBynder\Controller\PickerController;
use Terminal42\ContaoBynder\EventListener\FilesCopyButtonListener;
use Terminal42\ContaoBynder\ImageHandler;
use Terminal42\ContaoBynder\Picker\BynderAssetPickerProvider;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(BynderAssetPickerProvider::class)
        ->args([
            service('knp_menu.factory'),
            service('router'),
            service('security.token_storage'),
            service('assets.packages'),
        ])
    ;

    $services->set(Api::class)
        ->factory([Api::class, 'create'])
        ->args(['settings'])
    ;

    $services->set(ImageHandler::class)
        ->args([
            service(Api::class),
            service('logger'),
            'derivativeName',
            'derivativeOptions',
            'targetDir',
            service('contao.framework'),
            '%kernel.project_dir%',
            '%contao.upload_path%',
        ])
        ->tag('monolog.logger', [
            'channel' => 'terminal42.contao_bynder',
        ])
    ;

    $services->set(ApiController::class)
        ->public()
        ->args([
            service(Api::class),
            service('database_connection'),
            service('contao.framework'),
            service(ImageHandler::class),
        ])
        ->public()
    ;

    $services->set(PickerController::class)
        ->args([
            service('contao.framework'),
            service('contao.menu.renderer'),
            service('contao.picker.builder'),
            service('assets.packages'),
        ])
        ->public()
    ;

    $services->set(FilesCopyButtonListener::class);
};
