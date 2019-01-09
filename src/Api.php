<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2018, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder;

use Bynder\Api\Impl\BynderApi;
use Bynder\Api\Impl\Oauth\Credentials;
use Bynder\Api\Impl\Oauth\OauthRequestHandler;

/**
 * This is just here because the php-sdk of bynder does not allow you to access
 * the base url
 */
class Api extends BynderApi
{
    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
