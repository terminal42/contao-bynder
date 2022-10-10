<?php

declare(strict_types=1);

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Picker;

use Contao\BackendUser;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\Validator;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BynderAssetPickerProvider implements PickerProviderInterface
{
    /**
     * @var FactoryInterface
     */
    private $menuFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->menuFactory = $menuFactory;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns the unique name for this picker.
     */
    public function getName(): string
    {
        return 'bynderAssetPicker';
    }

    /**
     * Returns the URL to the picker based on the current value.
     */
    public function getUrl(PickerConfig $config): string
    {
        return $this->generateUrl($config);
    }

    /**
     * Creates the menu item for this picker.
     */
    public function createMenuItem(PickerConfig $config): ItemInterface
    {
        $GLOBALS['TL_CSS'][] = 'bundles/terminal42contaobynder/app.css';

        $name = $this->getName();
        $extensions = $config->getExtra('extensions');
        $display = true;

        if (null !== $extensions) {
            $fieldConfig = explode(',', $extensions);
            $valid = ['jpg', 'jpeg', 'png', 'gif'];

            if (0 === \count(array_intersect($valid, $fieldConfig))) {
                $display = false;
            }
        }

        return $this->menuFactory->createItem(
            $name,
            [
                'label' => 'Bynder Asset Management',
                'linkAttributes' => ['class' => $name],
                'current' => $this->isCurrent($config),
                'uri' => $this->generateUrl($config),
                'display' => $display,
            ]
        );
    }

    /**
     * Returns whether the picker supports the given context.
     */
    public function supportsContext(string $context): bool
    {
        return 'file' === $context && !\in_array('1', (array) $this->getUser()->bynder_disable, true);
    }

    /**
     * Returns whether the picker supports the given value.
     */
    public function supportsValue(PickerConfig $config): bool
    {
        if ('file' === $config->getContext()) {
            return Validator::isUuid($config->getValue());
        }

        return false;
    }

    /**
     * Returns whether the picker is currently active.
     */
    public function isCurrent(PickerConfig $config): bool
    {
        return $config->getCurrent() === $this->getName();
    }

    /**
     * Generates the URL for the picker.
     */
    private function generateUrl(PickerConfig $config): string
    {
        $params = array_merge(
            [
                'popup' => '1',
                'picker' => $config->cloneForCurrent($this->getName())->urlEncode(),
            ]
        );

        return $this->router->generate('bynder_asset_picker', $params);
    }

    /**
     * Returns the back end user object.
     *
     * @throws \RuntimeException
     */
    private function getUser(): BackendUser
    {
        if (null === $this->tokenStorage) {
            throw new \RuntimeException('No token storage provided');
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('No token provided');
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            throw new \RuntimeException('The token does not contain a back end user object');
        }

        return $user;
    }
}
