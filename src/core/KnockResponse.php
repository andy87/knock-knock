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



    /** @var string  */
    private string $content;

    /** @var int  */
    private int $httpCode;

    /** @var ?KnockRequest  */
    private ?KnockRequest $knockRequest = null;


    /**
     * KnockResponse constructor.
     *
     * @param ?resource $ch
     *
     * @throws Exception
     */
    public function __construct( $ch = null, ?KnockRequest $knockRequest = null )
    {
        if ( $ch )
        {
            $this->setHttpCode( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) );

            $this->setContent( curl_exec( $ch ) );
        }

        if ( $knockRequest )
        {
            $knockRequest->setCurlInfo( curl_getinfo( $ch ) );

            $this->setRequest($knockRequest);
        }
    }

    /**
     * @param int $httpCode
     *
     * @return void
     */
    public function setHttpCode( int $httpCode )
    {
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
     * @param string $content
     *
     * @return void
     */
    public function setContent( string $content )
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return KnockRequest
     */
    public function getRequest(): KnockRequest
    {
        return $this->knockRequest;
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
}