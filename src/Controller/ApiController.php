<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ContaoBynder\Api;
use Terminal42\ContaoBynder\ImageHandler;

#[Route(defaults: ['_scope' => 'backend'])]
class ApiController
{
    public function __construct(
        private readonly Api $api,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly ImageHandler $imageHandler,
    ) {
    }

    #[Route(path: '/_bynder_api/metaproperties', name: 'bynder_api_metaproperties')]
    public function mediapropertiesAction(Request $request): JsonResponse
    {
        /** @var PromiseInterface $promise */
        $promise = $this->api->getAssetBankManager()->getMetaproperties('type=image&count=1');

        $properties = $promise->wait();

        return new JsonResponse($properties);
    }

    #[Route(path: '/_bynder_api/images', name: 'bynder_api_images')]
    public function imagesAction(Request $request): JsonResponse
    {
        $limit = 25;
        $page = $request->query->getInt('page', 1);
        $preSelected = (array) explode(',', (string) $request->query->get('preSelected'));

        $queryString = Request::normalizeQueryString(
            http_build_query(array_merge($request->query->all(), [
                'limit' => $limit,
                'type' => 'image', // Maybe one day we'll support other stuff?
                'orderBy' => 'name asc',
                'count' => 1,
                'page' => $page,
            ]), '', '&'),
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

    #[Route(path: '/_bynder_api/download', name: 'bynder_api_download')]
    public function downloadAction(Request $request): JsonResponse
    {
        $mediaId = preg_replace('/[^a-zA-Z\d-]/', '', (string) $request->query->get('mediaId'));

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

    private function prepareImage(array $imageData, array $downloaded, array $preSelected): array
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
                $this->formatFilesize((int) $imageData['fileSize']),
                $imageData['width'],
                $imageData['height'],
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

    private function formatFilesize(int $bytes): string
    {
        $this->framework->initialize();

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);

        $system->loadLanguageFile('default');

        return $system->getReadableSize($bytes);
    }

    private function fetchDownloaded(array $media): array
    {
        $bynderIds = [];

        foreach ($media as $imageData) {
            $bynderIds[] = $imageData['id'];
        }

        $stmt = $this->connection->executeQuery(
            'SELECT uuid,bynder_id,bynder_hash FROM tl_files WHERE bynder_id IN (?)',
            [$bynderIds],
            [Connection::PARAM_INT_ARRAY],
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

    private function calculatePagination(int $page, int $limit, int $total): array
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
