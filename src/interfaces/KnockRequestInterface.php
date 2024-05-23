<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Интерфейс класса запроса
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99a
 */

namespace andy87\knock_knock\interfaces;

/**
 * Interface KnockRequestInterface
 *
 * @package andy87\knock_knock\interfaces
 */
interface KnockRequestInterface
{
    /** @var int  */
    public const STATUS_PREPARE = 0;
    /** @var int  */
    public const STATUS_PROCESSING = 1;
    /** @var int  */
    public const STATUS_COMPLETE = 2;



    /** @var string  */
    public const SETUP_HOST = 'host';

    /** @var string  */
    public const SETUP_PROTOCOL = 'protocol';
    /** @var string  */
    public const SETUP_ENDPOINT = 'endpoint';
    /** @var string  */
    public const SETUP_METHOD = 'method';
    /** @var string  */
    public const SETUP_CONTENT_TYPE = 'contentType';
    /** @var string  */
    public const SETUP_DATA = 'data';
    /** @var string  */
    public const SETUP_POST_FIELD = 'postField';
    /** @var string  */
    public const SETUP_HEADERS = 'headers';
    /** @var string  */
    public const SETUP_CURL_OPTIONS = 'curlOptions';
    /** @var string  */
    public const SETUP_CURL_INFO = 'curlInfo';

    /**
     * @var array
     */
    public const SETUPS = [
        self::SETUP_HOST,
        self::SETUP_PROTOCOL,
        self::SETUP_ENDPOINT,
        self::SETUP_METHOD,
        self::SETUP_CONTENT_TYPE,
        self::SETUP_DATA,
        self::SETUP_POST_FIELD,
        self::SETUP_HEADERS,
        self::SETUP_CURL_OPTIONS,
        self::SETUP_CURL_INFO,
    ];



    /**
     * @param string $protocol
     *
     * @return self
     */
    public function setProtocol( string $protocol ): self;

    /**
     * @return ?string
     */
    public function getProtocol(): ?string;

    /**
     * @param string $host
     *
     * @return self
     */
    public function setHost( string $host ): self;

    /**
     * @return ?string
     */
    public function getHost(): ?string;

    /**
     * @return ?string
     */
    public function getUrl(): ?string;

    /**
     * @param string $method
     *
     * @return self
     */
    public function setMethod( string $method ): self;

    /**
     * @return ?string
     */
    public function getMethod(): ?string;

    /**
     * @param string $contentType
     *
     * @return self
     */
    public function setContentType( string $contentType ): self;

    /**
     * @return ?string
     */
    public function getContentType(): ?string;

    /**
     * @param $data
     *
     * @return self
     */
    public function setData($data): self;

    /**
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * @param array $headers
     *
     * @return self
     */
    public function setHeaders( array $headers ): self;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @param array $curlOptions
     *
     * @return self
     */
    public function setCurlOptions( array $curlOptions ): self;

    /**
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function addHeaders( string $key, string $value ): self;

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function addCurlOptions( string $key, mixed $value ): self;

    /**
     * @param ?string $key
     *
     * @return array
     */
    public function getCurlOptions( string $key = null ): ?array;

    /**
     * @param array $curlInfo
     *
     * @return self
     */
    public function setCurlInfo( array $curlInfo ): self;

    /**
     * @return array
     */
    public function getCurlInfo(): array;

    /**
     * @return array
     */
    public function getCurlParams(): array;

    /**
     * @return array
     */
    public function getParams(): array;
}