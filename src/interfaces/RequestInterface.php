<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Интерфейс класса запроса
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.1.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\interfaces;

/**
 * Interface RequestInterface
 *
 * @package andy87\knock_knock\interfaces
 */
interface RequestInterface
{
    /** @var int */
    public const STATUS_PREPARE = 0;
    /** @var int */
    public const STATUS_PROCESSING = 1;
    /** @var int */
    public const STATUS_COMPLETE = 2;


    /** @var string */
    public const SETUP_HOST = 'host';

    /** @var string */
    public const SETUP_PROTOCOL = 'protocol';
    /** @var string */
    public const SETUP_ENDPOINT = 'endpoint';
    /** @var string */
    public const SETUP_METHOD = 'method';
    /** @var string */
    public const SETUP_CONTENT_TYPE = 'contentType';
    /** @var string */
    public const SETUP_DATA = 'data';
    /** @var string */
    public const SETUP_POST_FIELD = 'postField';
    /** @var string */
    public const SETUP_HEADERS = 'headers';
    /** @var string */
    public const SETUP_CURL_OPTIONS = 'curlOptions';
    /** @var string */
    public const SETUP_CURL_INFO = 'curlInfo';


    /**
     * @param string $protocol
     *
     * @return self
     */
    public function setProtocol(string $protocol): self;

    /**
     * @param string $host
     *
     * @return self
     */
    public function setHost(string $host): self;

    /**
     * @param string $endpoint
     *
     * @return self
     */
    public function setEndpoint(string $endpoint): self;

    /**
     * @param string $method
     *
     * @return self
     */
    public function setMethod(string $method): self;


    /**
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function setHeader(string $key, string $value): self;

    /**
     * @param array $headers
     *
     * @return self
     */
    public function addHeaders(array $headers): self;

    /**
     * @param string $contentType
     *
     * @return self
     */
    public function setContentType(string $contentType): self;


    /**
     * @param mixed $data
     *
     * @return self
     */
    public function setData(mixed $data): self;


    /**
     * @param int $key
     * @param mixed $value
     *
     * @return self
     */
    public function setCurlOption(int $key, mixed $value): self;

    /**
     * @param array $curlOptions
     *
     * @return self
     */
    public function addCurlOptions(array $curlOptions): self;

    /**
     * @param array $curlInfo
     *
     * @return self
     */
    public function setCurlInfo(array $curlInfo): self;
}