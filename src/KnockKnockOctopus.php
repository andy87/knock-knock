<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Расширяет класс KnockKnock и предоставляет доступ к "простым" методам отправки запросов через ext cURL
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 1.0.0
 */

declare(strict_types=1);

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
 *
 * Покрытие тестами: 30%. @see KnockRequestTest
 */
class KnockKnockOctopus extends KnockKnock
{
    /** @var array  */
    public const HEADERS = [
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true
    ];



    /**
     * Инициализация объекта
     *      Добавление общих параметров для всех запросов
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockKnockOctopusTest::testInit()
     *
     * @tag #octopus #init
     */
    public function init(): void
    {
        $this->getCommonKnockRequest()->addCurlOptions(self::HEADERS );
    }



    // === Public ===

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
     * Test: @see KnockKnockOctopusTest::testGet()
     *
     * @tag #octopus #request #get
     */
    public function get( string $endpoint, array $params = [] ): KnockResponse
    {
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
     * Test: @see KnockKnockOctopusTest::testPost()
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

    /**
     * Возвращает объект KnockResponse с фейковыми данными
     *
     * @param array $fakeResponse
     * @param array $requestParams
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockKnockOctopusTest::testFakeResponse()
     *
     * @tag #octopus #request #fake
     */
    public function fakeResponse( array $fakeResponse, array $requestParams = [] ): KnockResponse
    {
        $this->setupRequest(
            $this->constructRequest(
                LibKnockMethod::GET,
                '/',
                $requestParams
            )
        );

        return $this->send( $fakeResponse );
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
        $this->validateIssetRealKnockRequest( $method, $endpoint, $data );

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
    private function validateIssetRealKnockRequest(string $method, string $endpoint, array $data = [] ): void
    {
        $knockRequestParams = [];

        if ( count($data) ) {
            $knockRequestParams[KnockRequestInterface::SETUP_DATA] = $data;
        }

        $knockRequestParams[KnockRequestInterface::SETUP_METHOD] = $method;

        $knockRequest = $this->constructRequest( $method, $endpoint, $knockRequestParams );

        $this->setupRequest( $knockRequest );
    }
}