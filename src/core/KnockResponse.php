<?php

namespace andy87\knock_knock\core;

use Exception;

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
 *
 * - @see KnockResponse::ERROR;
 */
class KnockResponse
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
     * @return void
     *
     * @throws Exception
     */
    public function setHttpCode( int $httpCode )
    {
        if ( $this->httpCode )
        {
            throw new Exception('Request is already set');
        }

        $this->httpCode = $httpCode;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param $content
     *
     * @return void
     *
     * @throws Exception
     */
    public function setContent( $content )
    {
        if ( $this->content )
        {
            throw new Exception('Content is already set');
        }

        $this->content = $content;
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
     * @throws Exception
     */
    public function setRequest( KnockRequest $knockRequest )
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

        $access = [ KnockRequest::CURL_OPTIONS, KnockRequest::CURL_INFO ];

        if ( in_array( $key, $access ) )
        {
            $curlParams = $this->knockRequest->getCurlParams();

            switch ( $key )
            {
                case KnockRequest::CURL_OPTIONS:
                    $resp = $curlParams[KnockRequest::CURL_OPTIONS];
                    break;

                case KnockRequest::CURL_INFO:
                    $resp = $curlParams[KnockRequest::CURL_INFO];
                    break;
            }

            if ( $resp ) return $resp;
        }

        throw new Exception('Bad key');
    }
}