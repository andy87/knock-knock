<?php

namespace andy87\knock_knock;

use Exception;
use andy87\knock_knock\helpers\KnockMethod;
use andy87\knock_knock\interfaces\KnockRequestInterface;
use andy87\knock_knock\core\{KnockKnock, KnockResponse };

/**
 * Class KnockOctopus
 *
 * @package andy87\knock_knock
 *
 * Fix not used:
 * - @see KnockKnockOctopus::get();
 * - @see KnockKnockOctopus::post();
 * - @see KnockKnockOctopus::put();
 * - @see KnockKnockOctopus::delete();
 * - @see KnockKnockOctopus::patch();
 * - @see KnockKnockOctopus::options();
 * - @see KnockKnockOctopus::head();
 * - @see KnockKnockOctopus::trace();
 */
class KnockKnockOctopus extends KnockKnock
{
    /**
     * @param string $endpoint
     * @param array $params
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function get( string $endpoint, array $params = [] ): KnockResponse
    {
        if ( count( $params ) ) {
            $endpoint .= '?' . http_build_query( $params );
        }

        return $this->commonMethod( $endpoint, $params, KnockMethod::GET );
    }

    /**
     * @param $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function post( $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::POST );
    }

    /**
     * @param string $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function put( string $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::PUT );
    }

    /**
     * @param string $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function delete( string $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::DELETE );
    }

    /**
     * @param string $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function patch( string $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::PATCH );
    }

    /**
     * @param string $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function options( string $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::OPTIONS );
    }

    /**
     * @param string $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function head( string $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::HEAD );
    }

    /**
     * @param string $endpoint
     * @param ?mixed $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function trace( string $endpoint, $data = null ): KnockResponse
    {
        return $this->commonMethod( $endpoint, $data, KnockMethod::TRACE );
    }



    // === Private ===

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param string $method
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    private function commonMethod( string $endpoint, $data, string $method ): KnockResponse
    {
        if ( $this->knockRequest === null )
        {
            $knockRequestParams = [];

            if ($method !== KnockMethod::GET && count($data) ) {
                $knockRequestParams[KnockRequestInterface::DATA] = $data;
            }

            $knockRequestParams[KnockRequestInterface::METHOD] = $method;

            $knockRequest = $this->constructRequest( $endpoint, $knockRequestParams );

            $this->setupRequest( $knockRequest );
        }

        return $this->send();
    }
}