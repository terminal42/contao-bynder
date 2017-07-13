<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder;

use Bynder\Api\IBynderApi;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

class ImageHandler
{
    /**
     * @var IBynderApi
     */
    private $api;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * ImageHandler constructor.
     *
     * @param Api    $api
     * @param string $uploadPath
     * @param string $targetDir
     */
    public function __construct(Api $api, $uploadPath, $targetDir)
    {
        $this->api = $api;
        $this->uploadPath = $uploadPath;
        $this->targetDir = trim($targetDir, '/');
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $mediaId
     *
     * @return string The file path relative to the uploadPath
     */
    public function importImage($mediaId)
    {
        $absoluteTargetPath = $this->getTargetPathForMediaId($mediaId);

        if (file_exists($absoluteTargetPath)) {

            return $this->getUploadPathRelativePath($absoluteTargetPath);
        }

        $promise = $this->api->getRequestHandler()->sendRequestAsync('GET', 'api/v4/media/' . $mediaId . '/download', [
            'query' => 'type=original' // TODO, the original is never what we want!
        ]);

        $location = $promise->wait();

        $client = new Client();
        $result = $client->request('GET', $location['s3_file']);
        $content = $result->getBody()->getContents();

        // Dump the contents
        $this->filesystem->dumpFile($absoluteTargetPath, $content);

        return $this->getUploadPathRelativePath($absoluteTargetPath);
    }

    /**
     * @param $absolutePath
     *
     * @return string
     */
    private function getUploadPathRelativePath($absolutePath)
    {
        return $this->filesystem->makePathRelative($absolutePath, $this->uploadPath);
    }

    /**
     * @param string $mediaId
     *
     * @return string
     */
    private function getTargetPathForMediaId($mediaId)
    {
        $promise = $this->api->getAssetBankManager()->getMediaInfo($mediaId);

        $info = $promise->wait();

        $extension = $info['extension'][0];

        return $this->uploadPath
            . DIRECTORY_SEPARATOR
            . $this->targetDir
            . DIRECTORY_SEPARATOR
            . $mediaId
            . $extension;
    }
}
