<?php

namespace andy87\knock_knock\core;

use andy87\knock_knock\interfaces\KnockRequestInterface;
use Exception;
use andy87\knock_knock\interfaces\KnockResponseInterface;

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see KnockResponse::getRequest();
 * - @see KnockResponse::getHttpCode();
 * - @see KnockResponse::getContent();
 * - @see KnockResponse::get();
 * - @see KnockResponse::replace();
 * - @see KnockResponse::getTrace();
 *
 * - @see KnockResponse::ERROR;
 */
class KnockResponse implements KnockResponseInterface
{
    /** @var int  */
    public const OK = 200;
    /** @var int  */
    public const ERROR = 500;


    /** @var string  */
    public const CONTENT = 'content';
    /** @var string  */
    public const HTTP_CODE = 'httpCode';



    /** @var  */
    private $content;

    /** @var int  */
    private int $httpCode;

    /** @var ?KnockRequest  */
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
        $this->setContent( $content );

        $this->setHttpCode( $httpCode );

        $this->setRequest( $knockRequest );
    }


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
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param mixed $content
     *
     * @return void
     *
     * @throws Exception
     */
    public function setContent( $content ): self
    {
        if ( $this->content )
        {
            throw new Exception('Content is already set');
        }

        $this->content = $content;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * @param KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function setRequest( KnockRequest $knockRequest ): self
    {
        if ( $this->knockRequest )
        {
            throw new Exception('Request is already set');
        }

        $this->knockRequest = $knockRequest;

        return $this;
    }

    /**
     * @return KnockRequest
     */
    public function getRequest(): KnockRequest
    {
        return $this->knockRequest;
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
                $this->setContent( $value );
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

    /**
     * Получение Trace лог истории вызовов методов
     */
    public function getTrace(): array
    {
        return debug_backtrace();
    }
}