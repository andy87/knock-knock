<?php

namespace andy87\knock_knock\core;

use andy87\knock_knock\helpers\KnockContentType;

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see KnockRequest::getUrl();
 * - @see KnockRequest::getContentType();
 * - @see KnockRequest::getHeaders();
 * - @see KnockRequest::addCurlOptions();
 * - @see KnockRequest::getCurlOptions();
 * - @see KnockRequest::getCurlInfo();
 * - @see KnockRequest::getData();
 * - @see KnockRequest::getProtocol();
 * - @see KnockRequest::getHost();
 * - @see KnockRequest::getMethod();
 */
class KnockRequest
{
    /** @var string  */
    public const HOST = 'host';
    /** @var string  */
    public const PROTOCOL = 'protocol';


    /** @var string  */
    public const METHOD = 'method';
    /** @var string  */
    public const CONTENT_TYPE = 'contentType';
    /** @var string  */
    public const DATA = 'data';
    /** @var string  */
    public const HEADERS = 'headers';


    /** @var string  */
    public const CURL_OPTIONS  = 'curlOptions';
    /** @var string  */
    public const CURL_INFO = 'curlInfo';



    /** @var string $protocol */
    private string $protocol;

    /** @var string $host */
    private string $host;


    /** @var string $url */
    private string $url;

    /** @var string $method */
    private string $method;

    /** @var string $contentType */
    private string $contentType;

    /** @var mixed $data */
    private $data;

    /** @var array $headers */
    private array $headers = [];


    /** @var array $curl */
    private array $curlParams = [
        self::CURL_INFO => [],
        self::CURL_OPTIONS => []
    ];



    /**
     *
     * @param string $url
     * @param array $params
     */
    public function __construct( string $url , array $params = [] )
    {
        $this->url = $url;

        $this->setupParams($params);
    }

    /**
     * @param array $params
     *
     * @return void
     */
    private function setupParams( array $params )
    {
        foreach ( $params as $param => $value )
        {
           switch ($param)
           {
                case self::METHOD:
                    $this->setMethod($value);
                    break;

                case self::CONTENT_TYPE:
                    $this->setContentType($value);
                    break;

                case self::HEADERS:
                    $this->setHeaders($value);
                    break;

                case self::DATA:
                    $this->setData($value);
                    break;

                case self::CURL_OPTIONS:
                    $this->setCurlOptions($value);
                    break;

                case self::CURL_INFO:
                    $this->setCurlInfo($value);
                    break;

                case self::PROTOCOL:
                    $this->setProtocol($value);
                    break;

                case self::HOST:
                    $this->setHost($value);
                    break;
           }
        }
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $method
     *
     * @return void
     */
    public function setMethod( string $method ): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }


    /**
     * @param string $contentType
     *
     * @return void
     */
    public function setContentType( string $contentType ): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders( array $headers ): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addHeaders( string $key, string $value ): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }


    /**
     * @param array $options
     *
     * @return void
     */
    public function setCurlOptions( array $options ): self
    {
        $this->curlParams[self::CURL_OPTIONS] = $options;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addCurlOptions( string $key, string  $value ): self
    {
        $this->curlParams[self::CURL_OPTIONS][$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->curlParams[self::CURL_OPTIONS];
    }

    /**
     * @param array $info
     *
     * @return void
     */
    public function setCurlInfo( array $info ): self
    {
        $this->curlParams[self::CURL_INFO] = $info;

        return $this;
    }

    /**
     * @return array
     */
    public function getCurlInfo(): array
    {
        return $this->curlParams[self::CURL_INFO];
    }

    /**
     * @param $value
     *
     * @return void
     */
    public function setData( $value ): self
    {
        $this->data = $value;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $protocol
     *
     * @return $this
     */
    public function setProtocol( string $protocol ): self
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $host
     *
     * @return $this
     */
    public function setHost( string $host ): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return array
     */
    public function getCurlParams(): array
    {
        return $this->curlParams;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return [
            self::METHOD => $this->getMethod(),
            self::CONTENT_TYPE => $this->getContentType(),
            self::HEADERS => $this->getHeaders(),
            self::DATA => $this->getData(),
            self::CURL_OPTIONS => $this->getCurlOptions(),
            self::CURL_INFO => $this->getCurlInfo(),
            self::PROTOCOL => $this->getProtocol(),
            self::HOST => $this->getHost()
        ];
    }
}