<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder;

use Contao\Automator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\StringUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageHandler
{
    private readonly string $targetDir;

    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly Api $api,
        private readonly LoggerInterface $logger,
        private $derivativeName,
        private array $derivativeOptions,
        $targetDir,
        private readonly ContaoFramework $contaoFramework,
        private $rootDir,
        private $uploadPath,
    ) {
        $this->targetDir = trim((string) $targetDir, '/');
        $this->filesystem = new Filesystem();
    }

    /**
     * @return string|false the Contao file system UUID on success or false if something went wrong
     */
    public function importImage(string $mediaId): string|false
    {
        /** @var PromiseInterface $promise */
        $promise = $this->api->getAssetBankManager()->getMediaInfo($mediaId);
        $media = $promise->wait();

        $uri = sprintf('images/media/%s/derivatives/%s/',
            $mediaId,
            $this->derivativeName,
        );

        if (0 !== \count($this->derivativeOptions)) {
            // Force booleans to be passed as booleans (http_build_query() converts false to 0)
            $options = $this->derivativeOptions;
            array_walk(
                $options,
                static function (&$v): void {
                    if (true === $v) {
                        $v = 'true';
                    }
                    if (false === $v) {
                        $v = 'false';
                    }
                },
            );

            $uri .= '?'.http_build_query($options, '', '&');
        }

        try {
            $stack = HandlerStack::create();
            $stack->push(Middleware::retry(
                static function ($retries, Request $request, Response|null $response = null): bool {
                    if ($retries >= 5) {
                        return false;
                    }

                    if ($response && 202 === $response->getStatusCode()) {
                        return true;
                    }

                    return false;
                },
                static function ($retries): int {
                    if ($retries >= 5) {
                        return 0;
                    }

                    return 4000; // 4 seconds
                },
            ));

            $client = new Client([
                'handler' => $stack,
            ]);

            $result = $client->request(
                'GET',
                $uri,
                [
                    'base_uri' => $this->api->getBaseUrl(),
                    'allow_redirects' => true,
                    'timeout' => 30, // In seconds
                ],
            );
        } catch (RequestException $e) {
            $this->logger->error(
                'Could not import the Bynder derivative.',
                [
                    'exception' => $e,
                ],
            );

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
                $this->logger->error(
                    'Could not import the Bynder derivative because the content type did not match. Got '.$contentType,
                    [
                        'content-type' => $contentType,
                    ],
                );

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

        // Test if the hash already exists
        $filesModel = $this->contaoFramework->getAdapter(FilesModel::class)->findOneBy('bynder_id', $mediaId);

        // We already have a file with this media ID but apparently it has changed
        // (otherwise we shouldn't land here)
        if (null !== $filesModel) {
            $model = $dbafs->moveResource($filesModel->path, $relativePath);
        } else {
            // New addition
            $model = $dbafs->addResource($relativePath);
            $model->bynder_id = $mediaId;
        }

        // Update the Bynder hash
        $model->bynder_hash = $media['idHash'];
        $model->save();

        return StringUtil::binToUuid($model->uuid);
    }

    private function getUploadPathRelativePath(string $absolutePath): string
    {
        return rtrim($this->filesystem->makePathRelative($absolutePath, $this->getAbsoluteProjectDir()), '/');
    }

    private function getTargetPathForMediaId(string $name, string $extension): string
    {
        $subfolder = '';

        if (mb_strlen($name) >= 2) {
            $subfolder = mb_strtolower(mb_substr($name, 0, 1))
                .mb_strtolower(mb_substr($name, 1, 1))
                .\DIRECTORY_SEPARATOR;
        }

        $path = $this->getAbsoluteTargetDir()
            .\DIRECTORY_SEPARATOR
            .$subfolder
            .$name
            .'.'
            .$extension;

        // Check if already exists
        $index = 1;

        while ($this->filesystem->exists($path)) {
            $path = $this->getAbsoluteTargetDir()
                .\DIRECTORY_SEPARATOR
                .$subfolder
                .$name
                .'_'.$index
                .'.'
                .$extension;

            ++$index;
        }

        return $path;
    }

    private function getAbsoluteProjectDir(): string
    {
        return realpath($this->rootDir);
    }

    private function getAbsoluteTargetDir(): string
    {
        return $this->getAbsoluteProjectDir()
            .\DIRECTORY_SEPARATOR
            .$this->uploadPath
            .\DIRECTORY_SEPARATOR
            .$this->targetDir;
    }

    /**
     * Ensure the target dir is public and symlinked.
     */
    private function ensureTargetDirIsPublic(): void
    {
        $file = $this->getAbsoluteTargetDir()
            .\DIRECTORY_SEPARATOR
            .'.public';

        if (!file_exists($file)) {
            $fs = new Filesystem();
            $fs->dumpFile($file, '');

            $automator = new Automator();
            $automator->generateSymlinks();
        }
    }
}
