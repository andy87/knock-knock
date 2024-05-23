<?php /**
 * KnockResponse
 *
 * @author Andrey and_y87 Kidin
 * @description Предоставляет доступ к "простым" методам отправки запросов через ext cURL
 *
 * @date 2024-05-22
 *
 * @version 0.87
 */

namespace andy87\knock_knock;

use Exception;
use andy87\knock_knock\lib\LibKnockMethod;
use andy87\knock_knock\interfaces\KnockRequestInterface;
use andy87\knock_knock\core\{ KnockKnock, KnockResponse };

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
    // === Public ===

    // --- Constructors ---

    /**
     * Конструктор Endpoint запроса с добавлением GET параметров в URL
     *
     * @param string $endpoint
     * @param array $params
     * @param bool $isFullUrl
     *
     * @return string
     *
     * @tag #octopus #construct #endpoint
     *
     * @throws Exception
     */
    public function constructEndpointUrl(string $endpoint, array $params = [], bool $isFullUrl = false ): string
    {
        if ( $isFullUrl )
        {
            $output = $this->_commonKnockRequest->getUrl( $endpoint, $this->getHost(), $params );

        } else {

            $getQuery = ( count( $params ) ) ? ('?' . http_build_query( $params )) : '';

            $output = $endpoint . $getQuery;
        }

        return $output;
    }



    // --- Methods ---

    /**
     * Отправка GET запроса
     *
     * @param string $endpoint
     * @param array $params
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #get
     */
    public function get( string $endpoint, array $params = [] ): KnockResponse
    {
        $endpoint = self::constructEndpointUrl( $endpoint, $params );

        return $this->commonMethod( LibKnockMethod::GET, $endpoint, $params );
    }


    /**
     * Отправка POST запроса
     *
     * @param $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #post
     */
    public function post( $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::POST, $endpoint, $data );
    }

    /**
     * Отправка PUT запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #put
     */
    public function put( string $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::PUT, $endpoint, $data );
    }

    /**
     * Отправка DELETE запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #delete
     */
    public function delete( string $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::DELETE, $endpoint, $data );
    }

    /**
     * Отправка PATCH запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #patch
     */
    public function patch( string $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::PATCH, $endpoint, $data );
    }

    /**
     * Отправка OPTIONS запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #options
     */
    public function options( string $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::OPTIONS, $endpoint, $data );
    }

    /**
     * Отправка HEAD запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #head
     */
    public function head( string $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::HEAD, $endpoint, $data );
    }

    /**
     * Отправка TRACE запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #trace
     */
    public function trace( string $endpoint, array $data = [] ): KnockResponse
    {
        return $this->commonMethod( LibKnockMethod::TRACE, $endpoint, $data );
    }



    // === Private ===

    /**
     * Общая логика для всех методов
     *
     * @param string $method Метод запроса
     * @param string $endpoint Эндпоинт запроса
     * @param array $data Данные запроса
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #octopus #request #common
     */
    private function commonMethod( string $method, string $endpoint, array $data = [] ): KnockResponse
    {
        $this->validateRealKnockRequest( $method, $endpoint, $data );

        return $this->send();
    }

    /**
     * Проверка наличия объекта с данными запроса и его создание при отсутствии
     *
     * @param string $method Метод запроса
     * @param string $endpoint Эндпоинт запроса
     * @param array $data Данные запроса
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #octopus #request #setup
     */
    private function validateRealKnockRequest(string $method, string $endpoint, array $data = []): void
    {
        if ( $this->getRealKnockRequest() === null )
        {
            $knockRequestParams = [
                KnockRequestInterface::SETUP_CURL_OPTIONS => [
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true
                ]
            ];

            if ( $method !== LibKnockMethod::GET && count($data) ) {
                $knockRequestParams[KnockRequestInterface::SETUP_DATA] = $data;
            }

            $knockRequestParams[KnockRequestInterface::SETUP_METHOD] = $method;

            $knockRequest = $this->constructRequest( $method, $endpoint, $knockRequestParams );

            $this->setupRequest( $knockRequest );
        }
    }
}