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
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\System;
use Environment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
        if (!$request->query->has('picker')) {
            throw new BadRequestHttpException('Bynder Asset picker is supposed to be used within the Contao picker.');
        }

        /** @var PickerBuilderInterface $pickerBuilder */
        $pickerBuilder = $this->get('contao.picker.builder');
        $picker = $pickerBuilder->createFromData($request->query->get('picker'));

        if (null === $picker) {
            throw new BadRequestHttpException('Bynder Asset picker is supposed to have some data.');
        }

        /** @var ContaoFrameworkInterface $framework */
        $framework = $this->get('contao.framework');
        $framework->initialize();

        /** @var System $system */
        $system = $framework->getAdapter(System::class);
        $system->loadLanguageFile('default');

        /** @var Controller $controller */
        $controller = $framework->getAdapter(\Contao\Controller::class);
        $controller->setStaticUrls();

        $template = new BackendTemplate('be_main');

        /** @var PickerBuilderInterface $pickerBuilder */
        $pickerBuilder = $this->get('contao.picker.builder');
        $picker = $pickerBuilder->createFromData($request->query->get('picker'));

        if (($menu = $picker->getMenu()) && $menu->count() > 1) {
            $template->pickerMenu = $this->get('contao.menu.renderer')->render($menu);
        }

        $template->main = $this->getInitHtml($picker); // TODO implement
        $template->title = 'Bynder Asset Management';
        $template->headline = 'Bynder Asset Management';
        $template->isPopup = true;
        $template->theme = Backend::getTheme();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->charset = Config::get('characterSet');

        return $template->getResponse();
    }

    /**
     * @param PickerInterface $picker
     *
     * @return string
     */
    private function getInitHtml(PickerInterface $picker)
    {
        $config = $picker->getConfig();

        $mode = $config->getExtra('fieldType');

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
<div class="tl_tree_radio"></div>
<div id="bynder_interface"></div>
<script>
window.initBynderInterface('#bynder_interface', {mode: '$mode', labels: $labels});
</script>
VIEW;
    }
}
