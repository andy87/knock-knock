<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Интерфейс основного класса
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

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
     * @param array $RequestConfig
     *
     * @return Request
     */
    public function constructRequest(string $method, string $endpoint, array $RequestConfig = []): Request;

    /**
     * @param array $responseParams
     * @param ?Request $Request
     *
     * @return Response
     */
    public function constructResponse(array $responseParams, ?Request $Request = null): Response;


    /**
     * @param Request $Request
     * @param array $options
     *
     * @return self
     */
    public function setupRequest(Request $Request, array $options = []): self;


    /**
     * @param array $fakeResponse
     *
     * @return Response
     */
    public function send(array $fakeResponse = []): Response;


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