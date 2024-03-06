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

        if (!isset($meta[ImageHandler::METADATA_KEY])) {
            return;
        }

        $event->set('bynder_id', $meta[ImageHandler::METADATA_KEY]['id']);
        $event->set('bynder_hash', $meta[ImageHandler::METADATA_KEY]['idHash']);

        if ([] === $this->metaConfig) {
            return;
        }

        try {
            $event->set('meta', serialize($this->retrieveMeta($meta[ImageHandler::METADATA_KEY])));
        } catch (\Throwable $t) {
            $this->logger->error(sprintf('Could not automatically add the meta data for Bynder media ID "%s". Reason: %s',
                $meta[ImageHandler::METADATA_KEY]['id'],
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
