<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder;

use Contao\Automator;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class ImageHandler
{
    public const METADATA_KEY = 't42_bynder';

    public function __construct(
        private readonly VirtualFilesystemInterface $virtualFilesystem,
        private readonly Api $api,
        private readonly LoggerInterface $logger,
        private $derivativeName,
        private array $derivativeOptions,
        private string $targetDir,
    ) {
    }

    /**
     * @return string|false the Contao file system UUID on success or false if something went wrong
     */
    public function importImage(string $mediaId): string|false
    {
        /** @var PromiseInterface $promise */
        $promise = $this->api->getAssetBankManager()->getMediaInfo($mediaId);
        $media = $promise->wait();

        $uri = $this->buildDerivativeUri($mediaId);

        $client = (new RetryableHttpClient(HttpClient::create())) // Retry
            ->withOptions(
                (new HttpOptions())
                    ->setBaseUri($this->api->getBaseUrl())
                    ->toArray(),
            )
        ;

        try {
            $response = $client->request('GET', $uri);
            $headers = $response->getHeaders();
            $fileContents = $response->getContent();
        } catch (ExceptionInterface $e) {
            $this->logger->error(
                'Could not import the Bynder derivative.',
                [
                    'exception' => $e,
                ],
            );

            return false;
        }

        // Only allow jpeg and png
        switch ($contentType = $headers['content-type'][0] ?? '') {
            case 'image/jpeg':
                $extension = 'jpg';
                break;
            case 'image/png':
                $extension = 'png';
                break;
            default:
                $this->logger->error(sprintf('Could not import Bynder derivative for Media ID "%s" because the Content-Type did not match. Got "%s".', $mediaId, $contentType));

                return false;
        }

        $media['name'] = trim($media['name']);

        // Dump the contents
        $this->ensureTargetDirIsPublic();
        $targetPath = $this->getTargetPathForMediaId($media['name'], $extension);
        $this->virtualFilesystem->write($targetPath, $fileContents);
        $item = $this->virtualFilesystem->get($targetPath);
        $uuid = $item->getUuid()?->toRfc4122();

        if (null === $uuid) {
            $this->logger->error(sprintf('Could not assign a UUID to the Bynder derivative for Media ID "%s".', $mediaId));

            return false;
        }

        $this->virtualFilesystem->setExtraMetadata($targetPath, [self::METADATA_KEY => $media]);

        return $uuid;
    }

    private function getTargetPathForMediaId(string $name, string $extension): string
    {
        $subfolder = trim($this->targetDir, '/').'/';

        if (mb_strlen($name) >= 2) {
            $subfolder .= mb_strtolower(mb_substr($name, 0, 1))
                .mb_strtolower(mb_substr($name, 1, 1))
                .'/';
        }

        $path = $subfolder.$name.'.'.$extension;

        // Check if already exists and increase index until we have a valid file name
        $index = 1;

        while ($this->virtualFilesystem->fileExists($path)) {
            $path = $subfolder.$name.'_'.$index.'.'.$extension;
            ++$index;
        }

        return $path;
    }

    /**
     * Ensure the target dir is public and symlinked.
     */
    private function ensureTargetDirIsPublic(): void
    {
        if (!$this->virtualFilesystem->directoryExists($this->targetDir)) {
            $this->virtualFilesystem->createDirectory($this->targetDir);
        }

        $publicFile = trim($this->targetDir, '/').'/.public';

        if (!$this->virtualFilesystem->fileExists($publicFile)) {
            $this->virtualFilesystem->write($publicFile, '');

            // Generate symlinks, unfortunately no nice way via DI yet
            (new Automator())->generateSymlinks();
        }
    }

    private function buildDerivativeUri(string $mediaId): string
    {
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

        return $uri;
    }
}
