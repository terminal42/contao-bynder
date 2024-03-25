<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder;

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\PermanentTokens\Configuration;

class Api extends BynderClient
{
    public function getBaseUrl(): string
    {
        return 'https://'.$this->configuration->getBynderDomain();
    }

    /**
     * Creates an instance of BynderClient using the settings provided.
     */
    public static function create(array $settings): static
    {
        return new static(new Configuration($settings['domain'], $settings['token']));
    }
}
