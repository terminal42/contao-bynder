<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder\EventListener;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\ContaoBynder\ImageHandler;

#[AsEventListener]
class StoreDbafsMetadataEventListener
{
    public function __invoke(StoreDbafsMetadataEvent $event): void
    {
        $meta = $event->getExtraMetadata();

        if (!isset($meta[ImageHandler::METADATA_KEY])) {
            return;
        }

        $event->set('bynder_id', $meta[ImageHandler::METADATA_KEY]['id']);
        $event->set('bynder_hash', $meta[ImageHandler::METADATA_KEY]['idHash']);
    }
}
