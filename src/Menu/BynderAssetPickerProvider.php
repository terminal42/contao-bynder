<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Menu;

use Contao\CoreBundle\Menu\AbstractMenuProvider;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class BynderAssetPickerProvider extends AbstractMenuProvider implements PickerMenuProviderInterface
{
    /**
     * @var string
     */
    private $uploadPath;

    /**
     * Constructor.
     *
     * @param RouterInterface $router
     * @param RequestStack    $requestStack
     * @param string          $uploadPath
     */
    public function __construct(RouterInterface $router, RequestStack $requestStack, $uploadPath)
    {
        parent::__construct($router, $requestStack);

        $this->uploadPath = $uploadPath;
    }

    /**
     * Checks if a context is supported.
     *
     * @param string $context
     *
     * @return bool
     */
    public function supports($context)
    {
        return 'file' === $context;
    }

    /**
     * Creates the menu.
     *
     * @param ItemInterface    $menu
     * @param FactoryInterface $factory
     */
    public function createMenu(ItemInterface $menu, FactoryInterface $factory)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $params = $this->getParametersFromRequest($request);

        unset($params['do']);

        $item = $factory->createItem(
            'bynder-asset',
            ['uri' => $this->router->generate('bynder_asset_picker', $params)]
        );

        $routeInfo = $this->router->match($request->getPathInfo());
        $isCurrent = 'bynder_asset_picker' === $routeInfo['_route'];

        $item->setLabel('Bynder Asset Management');
        $item->setLinkAttribute('class', 'bynder-asset');
        $item->setCurrent($isCurrent);

        $menu->addChild($item);
    }

    /**
     * Checks if a table is supported.
     *
     * @param string $table
     *
     * @return bool
     */
    public function supportsTable($table)
    {
        return false;
    }

    /**
     * Processes the selected value.
     *
     * @param string $value
     *
     * @return string
     */
    public function processSelection($value)
    {
        // TODO: Implement processSelection() method.
        // This is used when using the standalone picker, not the filetree
        // widget.
    }

    /**
     * Checks if a value can be handled.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function canHandle(Request $request)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        // Never called anyway
        return '';
    }
}
