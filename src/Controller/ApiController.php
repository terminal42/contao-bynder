<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Controller;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
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
        $downloaded = $this->fetchDownloaded($media);
        foreach ($media as $imageData) {
            $images[] = $this->prepareImage($imageData, $downloaded);
        }

        // TODO filter for valid images (extensions!)

        return new JsonResponse($images);
    }

    /**
     * @param array $imageData
     *
     * @return array
     */
    private function prepareImage(array $imageData, array $downloaded)
    {
        $thumb = (object) [
            'src' => $imageData['thumbnails']['mini'],
            'alt' => $imageData['name'],
        ];

        $bynderId = $imageData['id'];

        $data = [
            'bynder_id' => $bynderId,
           // 'value' => 'bynder-asset:' . $imageData['id'],
            'selected' => false, // TODO
            'downloaded' => false,
            'name' => $imageData['name'],
            'meta' => sprintf('%s (%sx%s px)',
                $this->formatFilesize($imageData['fileSize']),
                $imageData['width'],
                $imageData['height']
            ),
            'thumb' => $thumb
        ];

        if (isset($downloaded[$bynderId])) {
            $data['downloaded'] = true;
            $data['uuid'] = $downloaded[$bynderId];
        }

        return $data;
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

    /**
     * @param array $media
     *
     * @return array
     */
    private function fetchDownloaded(array $media)
    {
        $bynderIds = [];

        foreach ($media as $imageData) {
            $bynderIds[] = $imageData['id'];
        }

        $connection = $this->get('doctrine.dbal.default_connection');
        $stmt = $connection->executeQuery('SELECT uuid, bynder_id FROM tl_files WHERE bynder_id IN (?)',
            [$bynderIds],
            [Connection::PARAM_INT_ARRAY]
        );

        $downloaded = [];

        foreach ($stmt->fetchAll() as $row) {
            $downloaded[$row['bynder_id']] = StringUtil::binToUuid($row['uuid']);
        }

        return $downloaded;
    }
}
