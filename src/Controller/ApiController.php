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
use Terminal42\ContaoBynder\ImageHandler;

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
     * @Route("/_bynder_api/metaproperties", name="bynder_api_metaproperties")
     */
    public function mediapropertiesAction(Request $request)
    {
        $api = $this->get('terminal42.contao_bynder.api');

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $api->getAssetBankManager()->getMetaproperties('type=image&count=1');

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

        $queryString = Request::normalizeQueryString(
            http_build_query(array_merge($request->query->all(), [
                'limit' => 25,
                'type' => 'image', // Maybe one day we'll support other stuff?
                'isPublic' => 1, // only public images can be retrieved through derivatives
                'orderBy' => 'name asc',
            ]), null, '&')
        );

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $api->getAssetBankManager()->getMediaList($queryString);

        $media = $promise->wait();

        $images = [];
        $downloaded = $this->fetchDownloaded($media);
        foreach ($media as $imageData) {
            $images[] = $this->prepareImage($imageData, $downloaded);
        }

        return new JsonResponse($images);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/_bynder_api/download", name="bynder_api_download")
     */
    public function downloadAction(Request $request)
    {
        /** @var ImageHandler $imageHandler */
        $imageHandler = $this->get('terminal42.contao_bynder.image_handler');

        $mediaId = preg_replace('/[^a-zA-Z\d-]/', '', $request->query->get('mediaId'));

        $response = [
            'status' => 'OK',
            'uuid' => null,
        ];

        $result = $imageHandler->importImage($mediaId);

        if (false === $result) {
            $response['status'] = 'FAILED';
        } else {
            $response['uuid'] = $result;
        }

        return new JsonResponse($response);
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
        $bynderHash = $imageData['idHash'];

        $data = [
            'bynder_id' => $bynderId,
            'bynder_hash' => $bynderHash,
            'selected' => false, // TODO
            'downloaded' => false,
            'name' => $imageData['name'],
            'meta' => sprintf('%s (%sx%s px)',
                $this->formatFilesize($imageData['fileSize']),
                $imageData['width'],
                $imageData['height']
            ),
            'thumb' => $thumb,
        ];

        if (isset($downloaded[$bynderId])) {
            $data['downloaded'] = $downloaded[$bynderId]['bynder_hash'] === $bynderHash;
            $data['uuid'] = $downloaded[$bynderId]['uuid'];
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
        $stmt = $connection->executeQuery('SELECT uuid,bynder_id,bynder_hash FROM tl_files WHERE bynder_id IN (?)',
            [$bynderIds],
            [Connection::PARAM_INT_ARRAY]
        );

        $downloaded = [];

        foreach ($stmt->fetchAll() as $row) {
            $downloaded[$row['bynder_id']] = [
                'uuid' => StringUtil::binToUuid($row['uuid']),
                'bynder_hash' => $row['bynder_hash'],
            ];
        }

        return $downloaded;
    }
}
