<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Расширяет класс KnockKnock и предоставляет доступ к "простым" методам отправки запросов через ext cURL
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

namespace andy87\knock_knock;

use andy87\knock_knock\lib\Method;
use andy87\knock_knock\core\{ Handler, Response };
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\exception\{ ParamUpdateException, ParamNotFoundException, InvalidEndpointException, handler\InvalidMethodException };
use andy87\knock_knock\exception\request\{ InvalidHeaderException, InvalidRequestException, RequestCompleteException, StatusNotFoundException };

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
 * Покрытие тестами: 30%. @see RequestTest
 */
class KnockKnockOctopus extends Handler
{
    /** @var array */
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
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockOctopusTest::testInit()
     *
     * @tag #octopus #init
     */
    public function init(): void
    {
        $this->getterCommonRequest()->addCurlOptions(self::HEADERS);
    }



    // === Public ===

    // --- Methods ---

    /**
     * Отправка GET запроса
     *
     * @param string $endpoint
     * @param array $params
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * Test: @see KnockKnockOctopusTest::testGet()
     *
     * @tag #octopus #request #get
     */
    public function get(string $endpoint, array $params = []): Response
    {
        return $this->commonMethod(Method::GET, $endpoint, $params);
    }


    /**
     * Отправка POST запроса
     *
     * @param $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * Test: @see KnockKnockOctopusTest::testPost()
     *
     * @tag #octopus #request #post
     */
    public function post($endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::POST, $endpoint, $data);
    }

    /**
     * Отправка PUT запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #put
     */
    public function put(string $endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::PUT, $endpoint, $data);
    }

    /**
     * Отправка DELETE запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #delete
     */
    public function delete(string $endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::DELETE, $endpoint, $data);
    }

    /**
     * Отправка PATCH запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #patch
     */
    public function patch(string $endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::PATCH, $endpoint, $data);
    }

    /**
     * Отправка OPTIONS запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #options
     */
    public function options(string $endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::OPTIONS, $endpoint, $data);
    }

    /**
     * Отправка HEAD запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #head
     */
    public function head(string $endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::HEAD, $endpoint, $data);
    }

    /**
     * Отправка TRACE запроса
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #trace
     */
    public function trace(string $endpoint, array $data = []): Response
    {
        return $this->commonMethod(Method::TRACE, $endpoint, $data);
    }

    /**
     * Возвращает объект Response с фейковыми данными
     *
     * @param array $fakeResponse
     * @param array $requestParams
     *
     * @return Response
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|InvalidEndpointException|InvalidMethodException|RequestCompleteException|InvalidRequestException
     *
     * Test: @see KnockKnockOctopusTest::testFakeResponse()
     *
     * @tag #octopus #request #fake
     */
    public function fakeResponse(array $fakeResponse, array $requestParams = []): Response
    {
        $request =  $this->constructRequest(
            Method::GET,
            '/',
            $requestParams
        );
        $request->setFakeResponse($fakeResponse);

        return $this->send($request);
    }



    // === Private ===

    /**
     * Общая логика для всех методов
     *
     * @param string $method Метод запроса
     * @param string $endpoint Эндпоинт запроса
     * @param array $data Данные запроса
     *
     * @return Response
     *
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @tag #octopus #request #common
     */
    private function commonMethod(string $method, string $endpoint, array $data = []): Response
    {
        $this->validateIssetRealRequest($method, $endpoint, $data);

        return $this->send($this->_realRequest);
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
     * @throws InvalidEndpointException|InvalidMethodException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @tag #octopus #request #setup
     */
    private function validateIssetRealRequest(string $method, string $endpoint, array $data = []): void
    {
        $requestParams = [];

        if (count($data)) {
            $requestParams[RequestInterface::SETUP_DATA] = $data;
        }

        $requestParams[RequestInterface::SETUP_METHOD] = $method;

        $request = $this->constructRequest($method, $endpoint, $requestParams);

        $this->setupRequest($request);
    }
}