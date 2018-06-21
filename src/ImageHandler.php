<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder;

use Bynder\Api\IBynderApi;
use Contao\Automator;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Dbafs;
use Contao\StringUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
     * @var array
     */
    private $derivativeOptions;

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
     * @param array                    $derivativeOptions
     * @param                          $targetDir
     * @param ContaoFrameworkInterface $contaoFramework
     * @param                          $rootDir
     * @param string                   $uploadPath
     */
    public function __construct(Api $api, LoggerInterface $logger, $derivativeName, array $derivativeOptions = [], $targetDir, ContaoFrameworkInterface $contaoFramework, $rootDir, $uploadPath)
    {
        $this->api = $api;
        $this->logger = $logger;
        $this->derivativeName = $derivativeName;
        $this->derivativeOptions = $derivativeOptions;
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
        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $this->api->getAssetBankManager()->getMediaInfo($mediaId);
        $media = $promise->wait();

        $uri = sprintf('images/media/%s/derivatives/%s/',
            $mediaId,
            $this->derivativeName
        );

        if (0 !== count($this->derivativeOptions)) {

            // Force booleans to be passed as booleans (http_build_query() converts false to 0)
            $options = $this->derivativeOptions;
            array_walk($options, function(&$v) {
                if (true === $v) {
                    $v = 'true';
                }
                if (false === $v) {
                    $v = 'false';
                }
            });

            $uri .= '?' . http_build_query($options, null, '&');
        }

        try {
            $stack = HandlerStack::create();
            $stack->push(Middleware::retry(function (
                $retries,
                Request $request,
                Response $response = null,
                RequestException $exception = null
            ) {
                if ($retries >= 5) {
                    return false;
                }

                if ($response && 202 === $response->getStatusCode()) {
                    return true;
                }

                return false;
            }, function($retries) {
                if ($retries >= 5) {
                    return 0;
                }

                return 4000; // 4 seconds
            }));

            $client = new Client([
                'handler' => $stack,
            ]);

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

        $content = $result->getBody()->getContents();

        // Dump the contents
        $this->ensureTargetDirIsPublic();
        $absoluteTargetPath = $this->getTargetPathForMediaId($media['name'], $extension);
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
     * @param string $name
     * @param string $extension
     *
     * @return string
     */
    private function getTargetPathForMediaId($name, $extension)
    {
        $subfolder = '';

        if (mb_strlen($name) >= 2) {
            $subfolder = mb_strtolower(mb_substr($name, 0, 1))
                . mb_strtolower(mb_substr($name, 1, 1))
                . DIRECTORY_SEPARATOR;
        }

        $path = $this->getAbsoluteTargetDir()
            . DIRECTORY_SEPARATOR
            . $subfolder
            . $name
            . '.'
            . $extension;

        // Check if already exists
        $index = 1;
        while ($this->filesystem->exists($path)) {
            $path = $this->getAbsoluteTargetDir()
                . DIRECTORY_SEPARATOR
                . $subfolder
                . $name
                . '_' . $index
                . '.'
                . $extension;

            $index++;
        }

        return $path;
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

    /**
     * @return string
     */
    private function getAbsoluteTargetDir()
    {
        return $this->getAbsoluteProjectDir()
            . DIRECTORY_SEPARATOR
            . $this->uploadPath
            . DIRECTORY_SEPARATOR
            . $this->targetDir
            ;
    }

    /**
     * Ensure the target dir is public and symlinked.
     */
    private function ensureTargetDirIsPublic()
    {
        $file = $this->getAbsoluteTargetDir()
            . DIRECTORY_SEPARATOR
            . '.public';

        if (!file_exists($file)) {
            $fs = new Filesystem();
            $fs->dumpFile($file, '');

            $automator = new Automator();
            $automator->generateSymlinks();
        }
    }
}
