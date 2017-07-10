<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Controller;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;
use Environment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend"})
 */
class PickerController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/_bynder_asset_picker", name="bynder_asset_picker")
     */
    public function pickerAction(Request $request)
    {
        /** @var ContaoFrameworkInterface $framework */
        $framework = $this->get('contao.framework');
        $framework->initialize();

        /** @var System $system */
        $system = $framework->getAdapter(System::class);
        $system->loadLanguageFile('default');

        /** @var Controller $controller */
        $controller = $framework->getAdapter(\Contao\Controller::class);
        $controller->setStaticUrls();

        /** @var \Contao\CoreBundle\Menu\PickerMenuBuilderInterface $menuBuilder */
        $menuBuilder = $this->get('contao.menu.picker_menu_builder');

        $template = new BackendTemplate('be_main');
        $template->main = $this->getInitHtml('radio', 'singleSRC'); // TODO implement
        $template->title = 'Bynder Asset Management';
        $template->headline = 'Bynder Asset Management';
        $template->pickerMenu = $menuBuilder->createMenu($request->query->get('context'));
        $template->isPopup = true;
        $template->theme = Backend::getTheme();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->charset = Config::get('characterSet');

        return $template->getResponse();
    }

    /**
     * @param string $mode
     * @param string $fieldName
     *
     * @return string
     */
    private function getInitHtml($mode, $fieldName)
    {
        $labels = json_encode((object) [
            'reset' => $GLOBALS['TL_LANG']['MSC']['reset'],
            'apply' => $GLOBALS['TL_LANG']['MSC']['apply'],
            'filter' => $GLOBALS['TL_LANG']['MSC']['filter'],
            'search' => $GLOBALS['TL_LANG']['MSC']['search'],
            'keywords' => $GLOBALS['TL_LANG']['MSC']['keywords'],
            'loadingData' => $GLOBALS['TL_LANG']['MSC']['loadingData'],
            'noResult' => $GLOBALS['TL_LANG']['MSC']['noResult'],
        ]);

        return <<<VIEW
<div id="bynder_interface"></div>
<script>
window.initBynderInterface('#bynder_interface', {mode: '$mode', name: '$fieldName', labels: $labels});
</script>
VIEW;
    }
}
