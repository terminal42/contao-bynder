<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder;

use Bynder\Api\IBynderApi;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Dbafs;
use Contao\StringUtil;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

class ImageHandler
{
    /**
     * @var IBynderApi
     */
    private $api;
    /**
     * @var ContaoFrameworkInterface
     */
    private $contaoFramework;

    /**
     * @var string
     */
    private $rootDir;

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
     * @param Api                      $api
     * @param ContaoFrameworkInterface $contaoFramework
     * @param                          $rootDir
     * @param string                   $uploadPath
     * @param string                   $targetDir
     */
    public function __construct(Api $api, ContaoFrameworkInterface $contaoFramework, $rootDir, $uploadPath, $targetDir)
    {
        $this->api = $api;
        $this->contaoFramework = $contaoFramework;
        $this->rootDir = $rootDir;
        $this->uploadPath = $uploadPath;
        $this->targetDir = trim($targetDir, '/');
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $mediaId
     * @param string $mediaHash
     *
     * @return string The Contao file system UUID
     */
    public function importImage($mediaId, $mediaHash)
    {
        $absoluteTargetPath = $this->getTargetPathForMediaId($mediaId);

        // TODO, wait for the new API and get a preconfigured, high-res jpgeg here

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $this->api->getAssetBankManager()->getMediaInfo($mediaId);

        $media = $promise->wait();

        $client = new Client();
        $result = $client->request('GET', $media['thumbnails']['webimage']);
        $content = $result->getBody()->getContents();

        // Dump the contents
        $this->filesystem->dumpFile($absoluteTargetPath, $content);
        $relativePath = $this->getUploadPathRelativePath($absoluteTargetPath);

        /** @var Dbafs $dbafs */
        $dbafs = $this->contaoFramework->getAdapter(Dbafs::class);
        $model = $dbafs->addResource($relativePath);

        // Add bynder attributes
        $model->bynder_id = $mediaId;
        $model->bynder_hash = $mediaHash;
        $model->save();

        return StringUtil::binToUuid($model->uuid);
    }

    /**
     * @param $absolutePath
     *
     * @return string
     */
    private function getUploadPathRelativePath($absolutePath)
    {
        return rtrim($this->filesystem->makePathRelative($absolutePath, $this->getAbsoluteProjectDir()), '/');
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

        return $this->getAbsoluteProjectDir()
            . DIRECTORY_SEPARATOR
            . $this->uploadPath
            . DIRECTORY_SEPARATOR
            . $this->targetDir
            . DIRECTORY_SEPARATOR
            . $mediaId
            . '.'
            . $extension;
    }

    /**
     * @return string
     */
    private function getAbsoluteProjectDir()
    {
        return realpath($this->rootDir
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
        );
    }
}
