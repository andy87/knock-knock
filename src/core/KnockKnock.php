<?php

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\helpers\{ KnockMethod, KnockContentType };
use andy87\knock_knock\interfaces\{KnockKnockInterface, KnockRequestInterface, KnockResponseInterface};

/**
 * Class KnockKnock
 *
 * @package andy87\knock_knock
 *z
 * Fix not used:
 * - @see KnockKnock::getInstance();
 *
 * - @see KnockKnock::setupEventHandlers();
 * - @see KnockKnock::on();
 * - @see KnockKnock::off();
 * - @see KnockKnock::addExtension();
 * - @see KnockKnock::removeExtension();
 */
class KnockKnock implements KnockKnockInterface
{
    /** @var ?KnockKnock $instance Singleton */
    protected static ?KnockKnock $instance = null;


    /** @var ?KnockRequest $commonKnockRequest */
    protected ?KnockRequest $commonKnockRequest = null;

    /** @var ?KnockRequest $knockRequest */
    protected ?KnockRequest $knockRequest = null;


    /** @var callable[] */
    protected array $callbacks = [
        self::EVENT_CONSTRUCT_REQUEST => null,
        self::EVENT_BEFORE_SEND => null,
        self::EVENT_CONSTRUCT_RESPONSE => null,
        self::EVENT_AFTER_SEND => null,
    ];

    /** @var callable[] */
    protected array $extensions = [];


    /**
     * KnockKnock constructor.
     *
     * @param array $commonKnockRequestParams
     *
     * @throws Exception
     */
    public function __construct( array $commonKnockRequestParams )
    {
        $this->commonKnockRequest = new KnockRequest( '/', $commonKnockRequestParams );
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __call( $name, $arguments )
    {
        if ( isset( $this->extensions[$name] ) )
        {
            return $this->extensions[$name]( ...$arguments );
        }

        throw new Exception( "Method $name not found" );
    }


    /**
     * @param array $commonKnockRequestParams
     *
     * @return self
     */
    public static function getInstance( array $commonKnockRequestParams ): self
    {
        if ( static::$instance === null )
        {
            $classname = static::class;

            static::$instance = new $classname( $commonKnockRequestParams );
        }

        return static::$instance;
    }



    // === Construct ===

    /**
     * @param string $endpoint
     * @param array $knockRequestConfig
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public function constructRequest( string $endpoint, array $knockRequestConfig = [] ): KnockRequest
    {
        $params = array_merge( $knockRequestConfig, $this->commonKnockRequest->getParams() );

        $knockRequest = new KnockRequest( $endpoint, $params );

        $this->event( self::EVENT_CONSTRUCT_REQUEST, $knockRequest );

        return $knockRequest;
    }

    /**
     * @param array $KnockResponseParams
     * @param ?KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function constructResponse( array $KnockResponseParams, ?KnockRequest $knockRequest = null ): KnockResponse
    {
        $knockResponse = new KnockResponse(
            $KnockResponseParams[KnockResponseInterface::CONTENT] ?? null,
            $KnockResponseParams[KnockResponseInterface::HTTP_CODE] ?? KnockResponseInterface::OK,
                $knockRequest
        );

        $this->event( self::EVENT_CONSTRUCT_RESPONSE, $knockResponse );

        return $knockResponse;
    }



    // === Setup ===

    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return $this
     */
    public function setupRequest( KnockRequest $knockRequest, array $options = [] ): self
    {
        if ( count( $options ) ) {
            $knockRequest = $this->updateRequestParams( $knockRequest, $options );
        }

        $this->knockRequest = $knockRequest;

        return $this;
    }

    /**
     * @param callable[] $callbacks
     *
     * @return array
     */
    public function setupEventHandlers( array $callbacks ): array
    {
        foreach ( $callbacks as $event => $callback )
        {
            if ( isset( $this->callbacks[$event] ) ) {
                $this->callbacks[$event] = $callback;
            }
        }

        return $this->callbacks;
    }



    // === Response ===

    /**
     * @param array $fakeKnockResponseParams
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function send( array $fakeKnockResponseParams = [] ): KnockResponse
    {
        return $this->sendRequest( $this->knockRequest, $fakeKnockResponseParams );
    }


    /**
     * @param KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function getResponseOnSendCurlRequest( KnockRequest $knockRequest ): KnockResponse
    {
        $knockRequest->setStatusProcessing();

        $ch = curl_init();

        $knockRequest = $this->setupPostFields( $knockRequest );

        $curlParams = $knockRequest->getCurlParams();

        curl_setopt_array( $ch, $curlParams[KnockRequestInterface::CURL_OPTIONS] );

        $response = curl_exec( $ch );

        $knockRequest->setCurlInfo( curl_getinfo( $ch ) );

        $knockResponseParams = [
            KnockResponseInterface::CONTENT => $response,
            KnockResponseInterface::HTTP_CODE => curl_getinfo( $ch, CURLINFO_HTTP_CODE )
        ];

        $knockResponse = $this->constructResponse( $knockResponseParams, $knockRequest );

        curl_close( $ch );

        $knockRequest->setStatusComplete();

        $this->event( self::EVENT_AFTER_SEND, $knockResponse );

        return $knockResponse;
    }



    // === Поведения === Behavior === Callbacks ===

    /**
     * @param string $event
     * @param mixed $data
     *
     * @return ?mixed
     */
    public function event( string $event, $data )
    {
        if ( isset( $this->callbacks[$event] ) )
        {
            $callback = $this->callbacks[$event];

            return $callback( $this, $data );
        }

        return null;
    }

    /**
     * @param string $event
     * @param callable $callback
     *
     * @return void
     */
    public function on( string $event, callable $callback ): bool
    {
        if ( isset( $this->callbacks[$event] ) )
        {
            $this->callbacks[$event] = $callback;

            return true;
        }

        return false;
    }

    /**
     * @param string $event
     *
     * @return void
     */
    public function off( string $event ): bool
    {
        if ( isset( $this->callbacks[$event] ) && $this->callbacks[$event] )
        {
            $this->callbacks[$event] = null;

            return true;
        }

        return false;
    }


    // === Extensions ===

    /**
     * @param string $name
     * @param callable $callback
     *
     * @return bool
     */
    public function addExtension( string $name, callable $callback ): bool
    {
        if ( !isset( $this->extensions[$name] ) )
        {
            $this->extensions[$name] = $callback;

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function removeExtension( string $name ): bool
    {
        if ( isset( $this->extensions[$name] ) )
        {
            unset( $this->extensions[$name] );

            return true;
        }

        return false;
    }



    // === Private ===

    /**
     * @param KnockRequest $knockRequest
     * @param array $fakeKnockResponseParams
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    private function sendRequest( KnockRequest $knockRequest, array $fakeKnockResponseParams = [] ): KnockResponse
    {
        $knockRequest->setStatusProcessing();

        $this->setupRequest( $knockRequest, $this->commonKnockRequest->getParams() );

        $this->event( self::EVENT_BEFORE_SEND, $this->knockRequest );

        $knockResponse = (count($fakeKnockResponseParams))
            ? $this->constructResponse( $fakeKnockResponseParams, $this->knockRequest )
            : $this->getResponseOnSendCurlRequest( $this->knockRequest );

        $knockRequest->setStatusComplete();

        return $knockResponse;
    }

    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return KnockRequest
     */
    private function updateRequestParams( KnockRequest $knockRequest, array $options = [] ): KnockRequest
    {
        if ( count($options) )
        {
            $mapping = [
                KnockRequestInterface::CURL_OPTIONS => 'setCurlOptions',
                KnockRequestInterface::CURL_INFO => 'setCurlInfo',
                KnockRequestInterface::HEADERS => 'setHeaders',
                KnockRequestInterface::DATA => 'setData',
                KnockRequestInterface::METHOD => 'setMethod',
                KnockRequestInterface::CONTENT_TYPE => 'setContentType',
                KnockRequestInterface::PROTOCOL => 'setProtocol',
                KnockRequestInterface::HOST => 'setHost',
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

        return $knockRequest;
    }

    /**
     * @param KnockRequest $knockRequest
     *
     * @return KnockRequest
     */
    private function setupPostFields( KnockRequest $knockRequest ): KnockRequest
    {
        if ( $knockRequest->getMethod() !== KnockMethod::GET )
        {
            $data = $knockRequest->getData();

            if ( count( $data ) )
            {
                switch( $knockRequest->getContentType() )
                {
                    case KnockContentType::JSON:
                        $knockRequest->addCurlOptions( CURLOPT_POSTFIELDS, json_encode( $data ) );
                        break;

                    case KnockContentType::FORM:
                    case KnockContentType::MULTIPART:
                    case KnockContentType::XML:
                    case KnockContentType::TEXT:
                    default:
                    $knockRequest->addCurlOptions( CURLOPT_POSTFIELDS, $data );
                }
            }
        }

        return $knockRequest;
    }
}