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
 * the request handler directly which basically prevents you from doing anything
 * with the api that is not supported by the sdk yet...
 */
class Api extends BynderApi
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var OauthRequestHandler
     */
    private $requestHandler;

    /**
     * Api constructor.
     *
     * @param string              $baseUrl
     * @param OauthRequestHandler $requestHandler
     */
    public function __construct($baseUrl, OauthRequestHandler $requestHandler)
    {
        parent::__construct($baseUrl, $requestHandler);

        $this->baseUrl = $baseUrl;
        $this->requestHandler = $requestHandler;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return OauthRequestHandler
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
    }

    /**
     * Creates an instance of BynderApi using the settings provided.
     *
     * @param array $settings oauth credentials and settings to configure the BynderApi instance
     *
     * @throws \InvalidArgumentException oauth settings not valid, consumer key or secret not in array
     *
     * @return BynderApi instance
     */
    public static function create($settings)
    {
        if (isset($settings) && ($settings = self::validateSettings($settings))) {
            $credentials = new Credentials(
                $settings['consumerKey'],
                $settings['consumerSecret'],
                $settings['token'],
                $settings['tokenSecret']
            );
            $requestHandler = OauthRequestHandler::create($credentials, $settings['baseUrl']);

            return new static($settings['baseUrl'], $requestHandler);
        }
        throw new \InvalidArgumentException('Settings passed for BynderApi service creation are not valid.');
    }

    /**
     * Checks if the settings array passed is valid.
     *
     * @param $settings
     *
     * @return bool whether the settings array is valid
     */
    private static function validateSettings($settings)
    {
        if (!isset($settings['consumerKey']) || !isset($settings['consumerSecret'])) {
            return false;
        }
        $settings['token'] = isset($settings['token']) ? $settings['token'] : null;
        $settings['tokenSecret'] = isset($settings['tokenSecret']) ? $settings['tokenSecret'] : null;

        return $settings;
    }
}
