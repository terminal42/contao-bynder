<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder\EventListener;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Util\LocaleUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\ContaoBynder\Api;
use Terminal42\ContaoBynder\ImageHandler;
use Twig\Environment;

#[AsEventListener]
class StoreDbafsMetadataEventListener
{
    private const IMPORTANT_PART_SQUARE_LENGTH_IN_PX = 100;

    public function __construct(
        private Api $api,
        private Environment $twig,
        private LoggerInterface $logger,
        private array $metaConfig = [],
    ) {
    }

    public function __invoke(StoreDbafsMetadataEvent $event): void
    {
        $meta = $event->getExtraMetadata();

        if (!isset($meta[ImageHandler::MEDIA_KEY])) {
            return;
        }

        $event->set('bynder_id', $meta[ImageHandler::MEDIA_KEY]['id']);
        $event->set('bynder_hash', $meta[ImageHandler::MEDIA_KEY]['idHash']);

        $this->importMetadata($event, $meta[ImageHandler::MEDIA_KEY]);
        $this->importImportantPath($event, $meta[ImageHandler::MEDIA_KEY], $meta[ImageHandler::IMAGE_DIMENSIONS_KEY] ?? []);
    }

    private function importImportantPath(StoreDbafsMetadataEvent $event, array $mediaInfo, array $imageDimensions): void
    {
        if (
            !isset($imageDimensions['width'])
            || !isset($imageDimensions['height'])
            || !isset($mediaInfo['width'])
            || !isset($mediaInfo['height'])
            || !isset($mediaInfo['activeOriginalFocusPoint']['x'])
            || !isset($mediaInfo['activeOriginalFocusPoint']['y'])
        ) {
            return;
        }

        // Adjust the focus point of the original file to our relative file dimensions of
        // the derivative
        $x = (int) round($imageDimensions['width'] / $mediaInfo['width'] * $mediaInfo['activeOriginalFocusPoint']['x']);
        $y = (int) round($imageDimensions['height'] / $mediaInfo['height'] * $mediaInfo['activeOriginalFocusPoint']['y']);

        $x = $x - self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / 2;
        $y = $y - self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / 2;

        if ($x < 0 || $y < 0) {
            return;
        }

        // Our important part configuration is in percentages
        $event->set('importantPartX', $x / $imageDimensions['width']);
        $event->set('importantPartY', $y / $imageDimensions['height']);
        $event->set('importantPartWidth', self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / $imageDimensions['width']);
        $event->set('importantPartHeight', self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / $imageDimensions['height']);
    }

    private function importMetadata(StoreDbafsMetadataEvent $event, array $mediaInfo): void
    {
        if ([] === $this->metaConfig) {
            return;
        }

        try {
            $event->set('meta', serialize($this->retrieveMeta($mediaInfo)));
        } catch (\Throwable $t) {
            $this->logger->error(sprintf('Could not automatically add the meta data for Bynder media ID "%s". Reason: %s',
                $mediaInfo['id'],
                $t->getMessage(),
            ), ['exception' => $t]);
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function retrieveMeta(array $mediaInfo): array
    {
        $metaPropertyOptions = $this->api
            ->getAssetBankManager()
            ->getMetapropertyOptions(['ids' => implode(',', $mediaInfo['propertyOptions'] ?? [])])
            ->wait()
        ;

        $metaProperties = $this->api
            ->getAssetBankManager()
            ->getMetaproperties(['ids' => implode(',', array_map(static fn (array $option) => $option['metapropertyId'], $metaPropertyOptions)), 'options' => 0])
            ->wait()
        ;

        $meta = [];

        foreach ($this->metaConfig as $language => $languageConfig) {
            foreach ($languageConfig as $field => $valueTemplate) {
                $template = $this->twig->createTemplate($valueTemplate);
                $meta[$language][$field] = $this->twig->render(
                    $template,
                    $this->resolveCustomMetaPropertyOptionsForLanguage($mediaInfo, $metaPropertyOptions, $metaProperties, $language),
                );
            }
        }

        return $meta;
    }

    private function resolveCustomMetaPropertyOptionsForLanguage(array $mediaInfo, array $metaPropertyOptions, array $metaProperties, string $language): array
    {
        $mediaInfoResolved = $mediaInfo;
        $singleSelectProperties = [];

        foreach ($mediaInfo as $property => $value) {
            if (!str_starts_with($property, 'property_')) {
                continue;
            }

            $propertyName = substr($property, 9);

            if (!isset($metaProperties[$propertyName]) || !\is_array($value)) {
                continue;
            }

            if (!$metaProperties[$propertyName]['isMultiselect']) {
                $singleSelectProperties[$property] = true;
            }

            foreach ($metaPropertyOptions as $metaPropertyOption) {
                if ($metaPropertyOption['metapropertyId'] !== $metaProperties[$propertyName]['id']) {
                    continue;
                }

                foreach ($value as $k => $v) {
                    if ($metaPropertyOption['name'] === $v) {
                        $mediaInfoResolved[$property][$k] = $this->findMatchingLabel($metaPropertyOption['labels'], $language) ?? $metaPropertyOption['label'];
                    }
                }
            }
        }

        // Flatten the values that are single choices only
        foreach (array_keys($singleSelectProperties) as $singleSelectProperty) {
            $mediaInfoResolved[$singleSelectProperty] = $mediaInfoResolved[$singleSelectProperty][0];
        }

        return $mediaInfoResolved;
    }

    private function findMatchingLabel(array $labels, string $language): string|null
    {
        // Exact dialect match
        if (isset($labels[$language])) {
            return $labels[$language];
        }

        // Primary language match
        foreach ($labels as $langCode => $label) {
            if (LocaleUtil::getPrimaryLanguage($langCode) === $language) {
                return $label;
            }
        }

        return null;
    }
}
