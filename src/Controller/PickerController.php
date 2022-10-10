<?php

declare(strict_types=1);

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Controller;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\Environment;
use Contao\System;
use Knp\Menu\Renderer\RendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend"})
 */
class PickerController
{
    private ContaoFramework $framework;
    private RendererInterface $menuRenderer;
    private PickerBuilderInterface $pickerBuilder;

    public function __construct(ContaoFramework $framework, RendererInterface $menuRenderer, PickerBuilderInterface $pickerBuilder)
    {
        $this->framework = $framework;
        $this->menuRenderer = $menuRenderer;
        $this->pickerBuilder = $pickerBuilder;
    }

    /**
     * @return Response
     *
     * @Route("/_bynder_asset_picker", name="bynder_asset_picker")
     */
    public function pickerAction(Request $request)
    {
        if (!$request->query->has('picker')) {
            throw new BadRequestHttpException('Bynder Asset picker is supposed to be used within the Contao picker.');
        }

        $picker = $this->pickerBuilder->createFromData($request->query->get('picker'));

        if (null === $picker) {
            throw new BadRequestHttpException('Bynder Asset picker is supposed to have some data.');
        }

        $this->framework->initialize();

        System::loadLanguageFile('default');

        $template = new BackendTemplate('be_main');

        $picker = $this->pickerBuilder->createFromData($request->query->get('picker'));

        if (($menu = $picker->getMenu()) && $menu->count() > 1) {
            $template->pickerMenu = $this->menuRenderer->render($menu);
        }

        $template->main = $this->getInitHtml($picker);
        $template->title = 'Bynder Asset Management';
        $template->headline = 'Bynder Asset Management';
        $template->isPopup = true;
        $template->theme = Backend::getTheme();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->charset = Config::get('characterSet');

        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/terminal42contaobynder/app.js';

        return $template->getResponse();
    }

    /**
     * @return string
     */
    private function getInitHtml(PickerInterface $picker)
    {
        $config = $picker->getConfig();

        $mode = $config->getExtra('fieldType');
        $preSelected = json_encode((array) explode(',', $config->getValue()));

        $labels = json_encode((object) [
            'reset' => $GLOBALS['TL_LANG']['MSC']['reset'],
            'apply' => $GLOBALS['TL_LANG']['MSC']['apply'],
            'filter' => $GLOBALS['TL_LANG']['MSC']['filter'],
            'search' => $GLOBALS['TL_LANG']['MSC']['search'],
            'keywords' => $GLOBALS['TL_LANG']['MSC']['keywords'],
            'loadingData' => $GLOBALS['TL_LANG']['MSC']['loadingData'],
            'noResult' => $GLOBALS['TL_LANG']['MSC']['noResult'],
            'showOnly' => $GLOBALS['TL_LANG']['MSC']['showOnly'],
            'downloadFailed' => $GLOBALS['TL_LANG']['MSC']['bynderDownloadFailed'],
        ]);

        return <<<VIEW
            <div class="tl_tree_radio"></div>
            <div id="bynder_interface"></div>
            <script>
            window.initBynderInterface('#bynder_interface', {mode: '$mode', labels: $labels, preSelected: $preSelected});
            </script>
            VIEW;
    }
}
