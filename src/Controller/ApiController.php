<?php

declare(strict_types=1);

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ContaoBynder\Api;
use Terminal42\ContaoBynder\ImageHandler;

/**
 * @Route(defaults={"_scope" = "backend"})
 */
class ApiController
{
    private Api $api;
    private Connection $connection;
    private ContaoFramework $framework;
    private ImageHandler $imageHandler;

    public function __construct(Api $api, Connection $connection, ContaoFramework $framework, ImageHandler $imageHandler)
    {
        $this->api = $api;
        $this->connection = $connection;
        $this->framework = $framework;
        $this->imageHandler = $imageHandler;
    }

    /**
     * @return Response
     *
     * @Route("/_bynder_api/metaproperties", name="bynder_api_metaproperties")
     */
    public function mediapropertiesAction(Request $request)
    {
        /** @var PromiseInterface $promise */
        $promise = $this->api->getAssetBankManager()->getMetaproperties('type=image&count=1');

        $properties = $promise->wait();

        return new JsonResponse($properties);
    }

    /**
     * @return Response
     *
     * @Route("/_bynder_api/images", name="bynder_api_images")
     */
    public function imagesAction(Request $request)
    {
        $limit = 25;
        $page = $request->query->getInt('page', 1);
        $preSelected = (array) explode(',', $request->query->get('preSelected'));

        $queryString = Request::normalizeQueryString(
            http_build_query(array_merge($request->query->all(), [
                'limit' => $limit,
                'type' => 'image', // Maybe one day we'll support other stuff?
                'orderBy' => 'name asc',
                'count' => 1,
                'page' => $page,
            ]), '', '&')
        );

        /** @var PromiseInterface $promise */
        $promise = $this->api->getAssetBankManager()->getMediaList($queryString);

        $result = $promise->wait();
        $media = $result['media'];

        $images = [];
        $downloaded = $this->fetchDownloaded($media);

        foreach ($media as $imageData) {
            $images[] = $this->prepareImage($imageData, $downloaded, $preSelected);
        }

        return new JsonResponse([
            'images' => $images,
            'pagination' => $this->calculatePagination($page, $limit, (int) $result['count']['total']),
        ]);
    }

    /**
     * @return Response
     *
     * @Route("/_bynder_api/download", name="bynder_api_download")
     */
    public function downloadAction(Request $request)
    {
        $mediaId = preg_replace('/[^a-zA-Z\d-]/', '', $request->query->get('mediaId'));

        $response = [
            'status' => 'OK',
            'uuid' => null,
        ];

        $result = $this->imageHandler->importImage($mediaId);

        if (false === $result) {
            $response['status'] = 'FAILED';
        } else {
            $response['uuid'] = $result;
        }

        return new JsonResponse($response);
    }

    /**
     * @return array
     */
    private function prepareImage(array $imageData, array $downloaded, array $preSelected)
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
            'selected' => false,
            'downloaded' => false,
            'name' => $imageData['name'],
            'meta' => sprintf(
                '%s (%sx%s px)',
                $this->formatFilesize($imageData['fileSize']),
                $imageData['width'],
                $imageData['height']
            ),
            'thumb' => $thumb,
        ];

        if (isset($downloaded[$bynderId])) {
            $data['downloaded'] = $downloaded[$bynderId]['bynder_hash'] === $bynderHash;
            $data['uuid'] = $downloaded[$bynderId]['uuid'];

            if (\in_array($data['uuid'], $preSelected, true)) {
                $data['selected'] = true;
            }
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
        $this->framework->initialize();

        /** @var System $system */
        $system = $this->framework->getAdapter('Contao\System');

        $system->loadLanguageFile('default');

        return $system->getReadableSize($bytes);
    }

    /**
     * @return array
     */
    private function fetchDownloaded(array $media)
    {
        $bynderIds = [];

        foreach ($media as $imageData) {
            $bynderIds[] = $imageData['id'];
        }

        $stmt = $this->connection->executeQuery(
            'SELECT uuid,bynder_id,bynder_hash FROM tl_files WHERE bynder_id IN (?)',
            [$bynderIds],
            [Connection::PARAM_INT_ARRAY]
        );

        $downloaded = [];

        foreach ($stmt->fetchAllAssociative() as $row) {
            $downloaded[$row['bynder_id']] = [
                'uuid' => StringUtil::binToUuid($row['uuid']),
                'bynder_hash' => $row['bynder_hash'],
            ];
        }

        return $downloaded;
    }

    /**
     * @param int $page
     * @param int $limit
     * @param int $total
     *
     * @return array
     */
    private function calculatePagination($page, $limit, $total)
    {
        $totalPages = ceil($total / $limit);
        $hasPrevious = $page > 1;
        $hasNext = $page < $totalPages;
        $previous = max(1, $page - 1);
        $next = $page + 1;

        if ($next > $totalPages) {
            $next = $totalPages;
        }

        return [
            'totalImages' => $total,
            'totalPages' => $totalPages,
            'perPage' => $limit,
            'currentPage' => $page,
            'hasPrevious' => $hasPrevious,
            'hasNext' => $hasNext,
            'previous' => $previous,
            'next' => $next,
        ];
    }
}
