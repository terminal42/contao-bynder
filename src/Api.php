<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder;

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\PermanentTokens\Configuration;

class Api extends BynderClient
{
    public function getBaseUrl(): string
    {
        return 'https://' .  $this->configuration->getBynderDomain();
    }

    /**
     * Creates an instance of BynderClient using the settings provided.
     */
    public static function create(array $settings): static
    {
        return new static(new Configuration($settings['domain'], $settings['token']));
    }
}
