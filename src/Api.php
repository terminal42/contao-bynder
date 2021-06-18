<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder;

use Bynder\Api\Impl\BynderApi;
use Bynder\Api\Impl\Oauth\Credentials;
use Bynder\Api\Impl\Oauth\OauthRequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * This is just here because the php-sdk of bynder does not allow you to access
 * the base url.
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

    /**
     * Creates an instance of BynderApi using the settings provided.
     *
     * @param array $settings oauth credentials and settings to configure the BynderApi instance
     *
     * @throws InvalidArgumentException oauth settings not valid, consumer key or secret not in array
     *
     * @return BynderApi instance
     */
    public static function createLogged($settings, LoggerInterface $logger)
    {
        return static::create(array_merge($settings, ['logger' => $logger]));
    }

    /**
     * Creates an instance of BynderApi using the settings provided.
     *
     * @param array $settings oauth credentials and settings to configure the BynderApi instance
     *
     * @throws InvalidArgumentException oauth settings not valid, consumer key or secret not in array
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

            $stack = HandlerStack::create(new CurlHandler());

            if (isset($settings['logger'])) {
                $stack->push(static::getLoggingMiddleware($settings['logger']));
            }

            $stack->push(
                new Oauth1([
                    'consumer_key' => $credentials->getConsumerKey(),
                    'consumer_secret' => $credentials->getConsumerSecret(),
                    'token' => $credentials->getToken(),
                    'token_secret' => $credentials->getTokenSecret(),
                    'request_method' => Oauth1::REQUEST_METHOD_HEADER,
                    'signature_method' => Oauth1::SIGNATURE_METHOD_HMAC,
                ])
            );

            $requestOptions = [
                'base_uri' => $settings['baseUrl'],
                'handler' => $stack,
                'auth' => 'oauth',
            ];

            // Configures request Client (adding proxy, etc.)
            if (isset($settings['requestOptions']) && \is_array($settings['requestOptions'])) {
                $requestOptions += $settings['requestOptions'];
            }

            $requestClient = new Client($requestOptions);
            $requestHandler = OauthRequestHandler::create($credentials, $settings['baseUrl'], $requestClient);

            return new static($settings['baseUrl'], $requestHandler);
        }
        throw new InvalidArgumentException('Settings passed for BynderApi service creation are not valid.');
    }

    /**
     * Get the middleware that logs the elapsed time from request to response.
     */
    public static function getLoggingMiddleware(LoggerInterface $logger)
    {
        return function (callable $handler) use ($logger) {
            return function (RequestInterface $request, array $options) use ($handler, $logger) {
                $start = microtime(true);

                return $handler($request, $options)->then(function (ResponseInterface $response) use ($request, $start, $logger) {
                    $logger->debug(
                        sprintf('Bynder request elapsed time: %ss', microtime(true) - $start),
                        ['request' => $request->getUri(), 'method' => $request->getMethod()]
                    );

                    return $response;
                });
            };
        };
    }
}
