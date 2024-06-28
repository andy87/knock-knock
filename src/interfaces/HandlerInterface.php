<?php declare(strict_types=1);

/**
 * @name: knock-knock
 * @author Andrey and_y87 Kidin
 * @description Интерфейс основного класса
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.2
 */

namespace andy87\knock_knock\interfaces;

use andy87\knock_knock\core\{Request, Response};

/**
 * Interface KnockSender
 *
 * @package andy87\knock_knock\interfaces
 */
interface HandlerInterface
{
    /** @var string */
    public const EVENT_AFTER_INIT = 'afterInit';
    public const EVENT_CONSTRUCT_REQUEST = 'constructRequest';
    /** @var string */
    public const EVENT_BEFORE_SEND = 'beforeSend';
    /** @var string */
    public const EVENT_CURL_HANDLER = 'curlHandler';
    /** @var string */
    public const EVENT_CONSTRUCT_RESPONSE = 'constructResponse';
    /** @var string */
    public const EVENT_AFTER_SEND = 'afterSend';

    public const PARAMS = 'params';
    public const PARAM_HOST = 'host';
    public const PARAM_COMMON_REQUEST = 'commonRequest';
    public const PARAM_REAL_REQUEST = 'realRequest';
    public const PARAM_EVENT_HANDLERS = 'eventHandlers';
    public const PARAM_LOGS = 'logs';




    /**
     * @param string $host
     * @param array $commonRequestParams
     */
    public function __construct(string $host, array $commonRequestParams = []);

    /**
     * @param string $paramName
     *
     * @return mixed
     */
    public function __get(string $paramName);

    /**
     * @param string $host
     * @param array $commonRequestParams
     *
     * @return self
     */
    public static function getInstance(string $host, array $commonRequestParams): self;

    /**
     * @return array
     */
    public function getParams(): array;

    /**
     * @return void
     */
    public function init(): void;


    /**
     * @param string $method
     * @param string $endpoint
     * @param array $requestConfig
     *
     * @return Request
     */
    public function constructRequest(string $method, string $endpoint, array $requestConfig = []): Request;

    /**
     * @param array $responseParams
     * @param ?Request $request
     *
     * @return Response
     */
    public function constructResponse(array $responseParams, ?Request $request = null): Response;


    /**
     * @param Request $request
     * @param array $options
     *
     * @return self
     */
    public function setupRequest(Request $request, array $options = []): self;


    /**
     * @param RequestInterface $request
     *
     * @return Response
     */
    public function send(RequestInterface $request): Response;


    /**
     * @param string $eventKey
     * @param callable $callbacks
     *
     * @return ?bool
     */
    public function on(string $eventKey, callable $callbacks): ?bool;

    /**
     * @param string $eventKey
     *
     * @return ?bool
     */
    public function off(string $eventKey): ?bool;

    /**
     * @param string $eventKey
     * @param array $args
     *
     * @return mixed
     */
    public function callEventHandler(string $eventKey, array $args = []): mixed;

    /**
     * @param string $eventKey
     * @param callable $callback
     *
     * @return bool
     */
    public function changeEvent(string $eventKey, callable $callback): bool;
}