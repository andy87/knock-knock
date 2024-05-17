<?php

namespace andy87\knock_knock\core;

use andy87\knock_knock\interfaces\KnockRequestInterface;/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 */
class KnockRequest implements KnockRequestInterface
{
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
    /** @var array $headers */
    private array $headers = [];
    /** @var mixed $data */
    private $data;


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
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }



    // === Setters & Getters ===


    // --- Protocol ---

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


    // --- Host ---

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


    // --- Methods ---

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


    // --- ContentType ---

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


    // --- Headers ---

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders( array $headers ): self
    {
        foreach ( $headers as $key => $value ) {
            $this->addHeaders($key, $value);
        }

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


    // --- Data ---

    /**
     * @param $data
     *
     * @return void
     */
    public function setData( $data ): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }


    // --- Curl ---

    /**
     * @param array $curlOptions
     *
     * @return void
     */
    public function setCurlOptions( array $curlOptions ): self
    {
        $this->curlParams[self::CURL_OPTIONS] = $curlOptions;

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
     * @param array $curlInfo
     *
     * @return void
     */
    public function setCurlInfo( array $curlInfo ): self
    {
        $this->curlParams[self::CURL_INFO] = $curlInfo;

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
            self::URL => $this->getUrl(),
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



    // === Private ===

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
                case self::PROTOCOL:
                    $this->setProtocol($value);
                    break;

                case self::HOST:
                    $this->setHost($value);
                    break;

                case self::URL:
                    $this->url = $value;
                    break;

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
            }
        }
    }
}