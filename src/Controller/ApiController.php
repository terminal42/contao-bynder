<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend"})
 */
class ApiController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/_bynder_api/mediaproperties", name="bynder_api_mediaproperties")
     */
    public function mediapropertiesAction(Request $request)
    {
        $api = $this->get('terminal42.contao_bynder.api');

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $api->getAssetBankManager()->getMetaproperties();

        $properties = $promise->wait();

        return new JsonResponse($properties);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/_bynder_api/images", name="bynder_api_images")
     */
    public function imagesAction(Request $request)
    {
        $api = $this->get('terminal42.contao_bynder.api');

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $api->getAssetBankManager()->getMediaList($request->getQueryString());

        $media = $promise->wait();

        $images = [];
        foreach ($media as $imageData) {
            $images[] = $this->prepareImage($imageData);
        }

        return new JsonResponse($images);
    }

    /**
     * @param array $imageData
     *
     * @return array
     */
    private function prepareImage(array $imageData)
    {
        $thumb = (object) [
            'src' => $imageData['thumbnails']['mini'],
            'alt' => $imageData['name'],
        ];

        return [
            'uuid' => $imageData['id'],
            'selected' => false,
            'name' => $imageData['name'],
            'meta' => sprintf('%s (%sx%s px)',
                $this->formatFilesize($imageData['fileSize']),
                $imageData['width'],
                $imageData['height']
            ),
            'thumb' => $thumb
        ];
    }

    /**
     * @param int $bytes
     *
     * @return string
     */
    private function formatFilesize($bytes)
    {
        $framework = $this->get('contao.framework');
        $framework->initialize();

        /** @var \Contao\System $system */
        $system = $framework->getAdapter('Contao\System');

        $system->loadLanguageFile('default');

        return $system->getReadableSize($bytes);
    }
}
