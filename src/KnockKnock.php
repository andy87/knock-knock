<?php

namespace andy87\knock_knock;

use Exception;
use andy87\knock_knock\core\KnockRequest;
use andy87\knock_knock\core\KnockResponse;

/**
 * Class KnockKnock
 *
 * @package andy87\knock_knock
 *
 * Fix not used:
 * - @see KnockKnock::getInstance();
 * - @see KnockKnock::constructKnockRequest();
 * - @see KnockKnock::setRequest();
 * - @see KnockKnock::send();
 * - @see KnockKnock::getResponseOnSendRequest();
 * - @see KnockKnock::event();
 * - @see KnockKnock::on();
 * - @see KnockKnock::off();
 * - @see KnockKnock::useAuthorization();
 * - @see KnockKnock::useHeaders();
 * - @see KnockKnock::useContentType();
 *
 * - @see KnockKnock::TOKEN_BEARER;
 */
class KnockKnock
{
    /** @var string  */
    public const EVENT_CONSTRUCT_REQUEST = 'constructRequest';
    /** @var string  */
    public const EVENT_BEFORE_SEND = 'beforeSend';
    /** @var string  */
    public const EVENT_CONSTRUCT_RESPONSE = 'constructResponse';
    /** @var string  */
    public const EVENT_AFTER_SEND = 'afterSend';


    /** @var string  */
    public const TOKEN_BEARER = 'Bearer';
    /** @var string  */
    public const TOKEN_BASIC = 'Basic';



    /** @var ?KnockRequest $commonKnockRequest */
    private ?KnockRequest $commonKnockRequest;

    /** @var ?KnockRequest $knockRequest */
    private ?KnockRequest $knockRequest = null;


    /** @var ?KnockKnock $instance Singleton */
    private static ?KnockKnock $instance = null;


    /** @var callable[] */
    private array $callbacks = [
        self::EVENT_CONSTRUCT_REQUEST => null,
        self::EVENT_BEFORE_SEND => null,
        self::EVENT_CONSTRUCT_RESPONSE => null,
        self::EVENT_AFTER_SEND => null,
    ];



    /**
     * KnockKnock constructor.
     *
     * @param array $commonKnockRequestParams
     */
    public function __construct( array $commonKnockRequestParams )
    {
        $this->commonKnockRequest = new KnockRequest( '/', $commonKnockRequestParams );
    }

    /**
     * @param array $commonKnockRequestParams
     *
     * @return self
     */
    public function getInstance( array $commonKnockRequestParams ): self
    {
        if ( static::$instance === null )
        {
            $classname = static::class;

            static::$instance = new $classname( $commonKnockRequestParams );
        }

        return static::$instance;
    }

    /**
     * @param string $endpoint
     * @param array $params
     *
     * @return KnockRequest
     */
    public function constructKnockRequest( string  $endpoint, array $params = [] ): KnockRequest
    {
        $params = array_merge( (array) $this->commonKnockRequest, $params );

        $this->knockRequest = new KnockRequest( $endpoint, $params );

        $this->event( self::EVENT_CONSTRUCT_REQUEST, $this->knockRequest );

        return $this->knockRequest;
    }

    /**
     * @param array $KnockResponseParams
     * @param ?KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function constructKnockResponse( array $KnockResponseParams, ?KnockRequest $knockRequest = null ): KnockResponse
    {
        $knockResponse = new KnockResponse();

        $knockResponse->setHttpCode( $KnockResponseParams[KnockResponse::HTTP_CODE] ?? KnockResponse::OK );
        $knockResponse->setContent( $KnockResponseParams[KnockResponse::CONTENT] ?? '' );

        if ($knockRequest) {
            $knockResponse->setRequest( $knockRequest );
        }

        $this->event( self::EVENT_CONSTRUCT_RESPONSE, $knockResponse );

        return $knockResponse;
    }

    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return $this
     */
    public function setRequest( KnockRequest $knockRequest, array $options = [] ): self
    {
        if ( count( $options ) )
        {
            $this->updateRequestParams( $knockRequest, $options );
        }

        $this->knockRequest = $knockRequest;

        return $this;
    }

    /**
     * @param array $fakeKnockResponseParams
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function send( array $fakeKnockResponseParams = [] ): KnockResponse
    {
        if ( $this->knockRequest )
        {
            $this->event( self::EVENT_BEFORE_SEND, $this->knockRequest );

            return (count($fakeKnockResponseParams))
                ? $this->constructKnockResponse( $fakeKnockResponseParams, $this->knockRequest )
                : $this->getResponseOnSendRequest();
        }

        throw new Exception( 'Request is not set' );
    }

    /**
     * @return KnockResponse
     *
     * @throws Exception
     */
    private function getResponseOnSendRequest(): KnockResponse
    {
        $ch = curl_init();

        $curlParams = $this->knockRequest->getCurlParams();

        curl_setopt_array( $ch, $curlParams[KnockRequest::CURL_OPTIONS] );

        $knockResponse = new KnockResponse( $ch, $this->knockRequest );

        $this->event( self::EVENT_CONSTRUCT_RESPONSE, $knockResponse );

        curl_close( $ch );

        $this->event( self::EVENT_AFTER_SEND, $knockResponse );

        return $knockResponse;
    }

    /**
     * @param string $event
     * @param mixed $object
     *
     * @return void
     */
    private function event(string $event, $object )
    {
        if ( isset( $this->callbacks[$event] ) && $this->callbacks[$event] )
        {
            $this->$event( $this, $object );
        }
    }

    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return void
     */
    private function updateRequestParams( KnockRequest $knockRequest, array $options = [] )
    {
        if ( count($options) )
        {
            $mapping = [
                KnockRequest::CURL_OPTIONS => 'setCurlOptions',
                KnockRequest::CURL_INFO => 'setCurlInfo',
                KnockRequest::HEADERS => 'setHeaders',
                KnockRequest::DATA => 'setData',
                KnockRequest::METHOD => 'setMethod',
                KnockRequest::CONTENT_TYPE => 'setContentType',
                KnockRequest::PROTOCOL => 'setProtocol',
                KnockRequest::HOST => 'setHost',
            ];

            foreach ( $options as $key => $value )
            {
                if ( isset( $mapping[$key] ) )
                {
                    $func = $mapping[$key];

                    $knockRequest->$func( $value );
                }
            }
        }
    }

    /**
     * @param string $event
     * @param callable $callback
     *
     * @return void
     */
    public function on( string $event, callable $callback )
    {
        if ( isset( $this->callbacks[$event] ) )
        {
            $this->callbacks[$event] = $callback;
        }
    }

    /**
     * @param string $event
     *
     * @return void
     */
    public function off( string $event )
    {
        if ( isset( $this->callbacks[$event] ) )
        {
            $this->callbacks[$event] = null;
        }
    }

    /**
     * @param string $token
     * @param string $authType
     *
     * @return $this
     */
    public function useAuthorization( string $token, string $authType = self::TOKEN_BASIC ): KnockKnock
    {
        $this->commonKnockRequest->addHeaders( 'Authorization', "$authType $token" );

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function useHeaders( array $headers ): KnockKnock
    {
        $headers = array_merge( $this->commonKnockRequest->getHeaders(), $headers );

        $this->commonKnockRequest->setHeaders( $headers );

        return $this;
    }

    /**
     * @param string $ContentType
     *
     * @return $this
     */
    public function useContentType( string $ContentType ): KnockKnock
    {
        $this->commonKnockRequest->setContentType( $ContentType );

        return $this;
    }
}