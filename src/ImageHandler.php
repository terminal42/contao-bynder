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
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Dbafs;
use Contao\StringUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageHandler
{
    /**
     * @var IBynderApi
     */
    private $api;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $derivativeName;

    /**
     * @var string
     */
    private $targetDir;

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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * ImageHandler constructor.
     *
     * @param Api                      $api
     * @param LoggerInterface          $logger
     * @param                          $derivativeName
     * @param                          $targetDir
     * @param ContaoFrameworkInterface $contaoFramework
     * @param                          $rootDir
     * @param string                   $uploadPath
     */
    public function __construct(Api $api, LoggerInterface $logger, $derivativeName, $targetDir, ContaoFrameworkInterface $contaoFramework, $rootDir, $uploadPath)
    {
        $this->api = $api;
        $this->logger = $logger;
        $this->derivativeName = $derivativeName;
        $this->targetDir = trim($targetDir, '/');
        $this->contaoFramework = $contaoFramework;
        $this->rootDir = $rootDir;
        $this->uploadPath = $uploadPath;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $mediaId
     *
     * @return string|false The Contao file system UUID on success or false if something went wrong.
     */
    public function importImage($mediaId)
    {
        $uri = sprintf('images/media/%s/derivatives/%s/',
            $mediaId,
            $this->derivativeName
        );

        try {
            $client = new Client();
            $result = $client->request('GET', $uri, [
                'base_uri' => $this->api->getBaseUrl(),
                'allow_redirects' => true,
                'timeout' => 8,
            ]);
        } catch (RequestException $e) {

            $this->logger->error('Could not import the Bynder derivative.', [
                'exception' => $e,
                'contao' => new ContaoContext(__METHOD__)
            ]);

            return false;
        }

        // Only allow jpeg and png
        switch ($contentType = $result->getHeader('Content-Type')[0]) {
            case 'image/jpeg':
                $extension = 'jpg';
                break;
            case 'image/png':
                $extension = 'png';
                break;
            default:
                $this->logger->error('Could not import the Bynder derivative because the content type did not match. Got ' . $contentType, [
                    'content-type' => $contentType,
                    'contao' => new ContaoContext(__METHOD__)
                ]);

                return false;
        }

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $this->api->getAssetBankManager()->getMediaInfo($mediaId);
        $media = $promise->wait();

        $content = $result->getBody()->getContents();

        // Dump the contents
        $absoluteTargetPath = $this->getTargetPathForMediaId($mediaId, $extension);
        $this->filesystem->dumpFile($absoluteTargetPath, $content);
        $relativePath = $this->getUploadPathRelativePath($absoluteTargetPath);

        /** @var Dbafs $dbafs */
        $dbafs = $this->contaoFramework->getAdapter(Dbafs::class);
        $model = $dbafs->addResource($relativePath);

        // Add bynder attributes
        $model->bynder_id = $mediaId;
        $model->bynder_hash = $media['idHash'];
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
     * @param string $extension
     *
     * @return string
     */
    private function getTargetPathForMediaId($mediaId, $extension)
    {
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
