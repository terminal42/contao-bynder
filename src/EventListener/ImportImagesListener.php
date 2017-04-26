<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Input;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\ContaoBynder\ImageHandler;

class ImportImagesListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ImageHandler
     */
    private $imageHandler;

    /**
     * ImportImagesListener constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param                          $requestStack
     * @param ImageHandler             $imageHandler
     */
    public function __construct(ContaoFrameworkInterface $framework, $requestStack, ImageHandler $imageHandler)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->imageHandler = $imageHandler;
    }

    /**
     * Import bynder-assets on pre ajax and replace them with their path on
     * the filesystem so the post ajax hook will find them and treat them
     * as a regular filesystem asset.
     */
    public function onExecutePreActions()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('POST' !== $request->getMethod()
            && 'reloadFileTree' !== $request->request->get('action')
        ) {
            return;
        }

        // Check if we need to import files.
        $values = explode("\t", $request->request->get('value', ''));
        $newValues = [];

        foreach ($values as $value) {
            if (preg_match('/^bynder-asset:(.*)/', $value, $matches)) {
                $newValues[] = $this->importFile($matches[1]);
            } else {
                $newValues[] = $value;
            }
        }

        $request->request->set('value', implode("\t", $newValues));
        Input::setPost('value', implode("\t", $newValues)); // hopefully we map this to the request one day.
    }

    /**
     * @param string $mediaId
     *
     * @return string
     */
    private function importFile($mediaId)
    {
        return rawurlencode($this->imageHandler->importImage($mediaId));
    }
}
