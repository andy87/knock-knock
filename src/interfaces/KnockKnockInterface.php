<?php

namespace andy87\knock_knock\interfaces;

use andy87\knock_knock\core\KnockRequest;
use andy87\knock_knock\core\KnockResponse;

/**
 * Interface KnockSender
 *
 * @package andy87\knock_knock\interfaces
 */
interface KnockKnockInterface
{
    /** @var string  */
    public const EVENT_CONSTRUCT_REQUEST = 'constructRequest';
    /** @var string  */
    public const EVENT_BEFORE_SEND = 'beforeSend';
    /** @var string  */
    public const EVENT_CONSTRUCT_RESPONSE = 'constructResponse';
    /** @var string  */
    public const EVENT_AFTER_SEND = 'afterSend';



    /**
     * @param array $commonKnockRequestParams
     *
     * @return self
     */
    public static function getInstance( array $commonKnockRequestParams ): self;


    /**
     * @param string $endpoint
     * @param array $knockRequestConfig
     *
     * @return KnockRequest
     */
    public function constructRequest( string $endpoint, array $knockRequestConfig = [] ): KnockRequest;

    /**
     * @param array $KnockResponseParams
     * @param ?KnockRequest $knockRequest
     *
     * @return KnockResponse
     */
    public function constructResponse( array $KnockResponseParams, ?KnockRequest $knockRequest = null ): KnockResponse;


    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return self
     */
    public function setupRequest( KnockRequest $knockRequest, array $options = [] ): self;

    /**
     * @param array $callbacks
     *
     * @return array
     */
    public function setupEventHandlers( array $callbacks ): array;


    /**
     * @param array $fakeKnockResponseParams
     *
     * @return KnockResponse
     */
    public function send( array $fakeKnockResponseParams = [] ): KnockResponse;


    /**
     * @param string $event
     * @param $data
     *
     * @return mixed
     */
    public function event( string $event, $data );

    /**
     * @param KnockRequest $knockRequest
     *
     * @return KnockResponse
     */
    public function getResponseOnSendCurlRequest( KnockRequest $knockRequest ): KnockResponse;
}