<?php

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\interfaces\{ KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * @property-read KnockRequest $request
 * @property-read int $httpCode
 * @property-read mixed $content
 * @property-read array $trace
 * @property-read array $curlOptions
 * @property-read array $curlInfo
 *
 * Fix not used:
 * - @see KnockResponse::replace();
 */
class KnockResponse implements KnockResponseInterface
{
    /** @var mixed $data */
    private $data;

    /** @var int $httpCode */
    private int $httpCode;


    /** @var ?KnockRequest $knockRequest */
    private ?KnockRequest $knockRequest = null;



    /**
     * KnockResponse constructor.
     *
     * @param $content
     * @param int $httpCode
     * @param KnockRequest $knockRequest
     *
     * @throws Exception
     */
    public function __construct( $content, int $httpCode, KnockRequest $knockRequest )
    {
        $this->setData( $content );

        $this->setHttpCode( $httpCode );

        $this->setRequest( $knockRequest );
    }

    /**
     * @param $name
     *
     * @return KnockRequest|array|int|mixed|void
     *
     * @throws Exception
     */
    public function __get($name)
    {
        switch ($name)
        {
            case self::REQUEST:
                return $this->getRequest();

            case self::HTTP_CODE:
                return $this->getHttpCode();

            case self::CONTENT:
                return $this->getContent();

            case self::TRACE:
                return $this->getTrace();

            case KnockRequestInterface::CURL_OPTIONS:
            case KnockRequestInterface::CURL_INFO:
                return $this->get($name);

            default:
                throw new Exception("Property `$name`not found on: " . __CLASS__);
        }
    }



    // === PUBLIC ===

    /**
     * @param int $httpCode
     *
     * @return self
     *
     * @throws Exception
     */
    public function setHttpCode( int $httpCode ): self
    {
        if ( $this->httpCode )
        {
            throw new Exception('Request is already set');
        }

        $this->httpCode = $httpCode;

        return $this;
    }

    /**
     * @return int
     */
    private function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param mixed $data
     *
     * @return void
     *
     * @throws Exception
     */
    public function setData( $data ): self
    {
        if ( $this->data )
        {
            throw new Exception('Content is already set');
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function replace( string $key, $value ): KnockResponse
    {
        switch ( $key )
        {
            case self::HTTP_CODE:
                $this->setHttpCode( $value );
                break;

            case self::CONTENT:
                $this->setData( $value );
                break;

            default:
                throw new Exception('Bad key');
        }

        return $this;
    }

    /**
     * @param string $key
     * @return array
     *
     * @throws Exception
     */
    public function get( string $key ): array
    {
        $resp = null;

        $access = [ KnockRequestInterface::CURL_OPTIONS, KnockRequestInterface::CURL_INFO ];

        if ( in_array( $key, $access ) )
        {
            $curlParams = $this->knockRequest->getCurlParams();

            switch ( $key )
            {
                case KnockRequestInterface::CURL_OPTIONS:
                    $resp = $curlParams[KnockRequestInterface::CURL_OPTIONS];
                    break;

                case KnockRequestInterface::CURL_INFO:
                    $resp = $curlParams[KnockRequestInterface::CURL_INFO];
                    break;
            }

            if ( $resp ) return $resp;
        }

        throw new Exception('Bad key');
    }



    // === PRIVATE ===

    /**
     * @return mixed
     */
    private function getContent()
    {
        return $this->data;
    }

    /**
     * @param KnockRequest $knockRequest
     *
     * @return void
     *
     * @throws Exception
     */
    private function setRequest( KnockRequest $knockRequest ): void
    {
        if ( $this->knockRequest )
        {
            throw new Exception('Request is already set');
        }

        $this->knockRequest = $knockRequest;
    }

    /**
     * @return KnockRequest
     */
    private function getRequest(): KnockRequest
    {
        return $this->knockRequest;
    }

    /**
     * Получение Trace лог истории вызовов методов
     */
    private function getTrace(): array
    {
        return debug_backtrace();
    }
}