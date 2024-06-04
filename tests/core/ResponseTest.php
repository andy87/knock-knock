<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса Handler
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.1.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\core;

use andy87\knock_knock\lib\Method;
use andy87\knock_knock\core\{ Operator, Request, Response };
use andy87\knock_knock\tests\helpers\{ PostmanEcho, UnitTestCore };
use andy87\knock_knock\interfaces\{ RequestInterface, ResponseInterface };
use andy87\knock_knock\exception\{ InvalidHostException, InvalidEndpointException, ParamNotFoundException, ParamUpdateException };
use andy87\knock_knock\exception\{ operator\InvalidMethodException,
    request\InvalidHeaderException,
    request\InvalidRequestException,
    request\RequestCompleteException,
    request\StatusNotFoundException
};

/**
 * Class ResponseTest
 *
 * Тесты для методов класса Response
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox
 *
 * @tag #test #response
 */
class ResponseTest extends UnitTestCore
{
    /** @var Operator $operator */
    private Operator $operator;


    /** @var Request $request */
    private Request $request;


    /** @var Response $response */
    private Response $response;



    /**
     * Установки
     *
     * @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException|InvalidEndpointException|ParamNotFoundException|InvalidMethodException|
     * @throws InvalidHeaderException|InvalidHostException|RequestCompleteException|InvalidRequestException
     *
     * @tag #test #response #setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setupObjects();
    }

    /**
     * Проверка создания объекта класса `Response`
     *      Тест ожидает, что объект будет создан
     *
     * @see Response::__construct()
     *
     * 
     *
     * @return void
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testConstructor
     *
     * @tag #test #response #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Operator::class, $this->operator );
        $this->assertInstanceOf(Request::class, $this->request );
        $this->assertInstanceOf(Response::class, $this->response );
    }

    /**
     * Проверка геттеров объекта класса `Response`
     *      Тест ожидает, что геттеры вернут ожидаемые значения
     *
     * Source: @see Response::__get()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testMagicGet
     *
     * @tag #test #response #getter
     */
    public function testMagicGet()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, [
            RequestInterface::SETUP_DATA => PostmanEcho::DATA,
            RequestInterface::SETUP_CURL_INFO => self::CURL_INFO,
            RequestInterface::SETUP_CURL_OPTIONS => self::CURL_OPTIONS,
        ]);

        $response = new Response(self::CONTENT, self::HTTP_CODE_OK, $request );

        $this->assertEquals(self::HTTP_CODE_OK, $response->httpCode );
        $this->assertEquals(self::CONTENT, $response->content );

        $jsonOriginal = json_encode($request->params);
        $jsonResponse = json_encode($response->request->params);
        $this->assertEquals( $jsonOriginal, $jsonResponse );

        $this->assertEquals(self::CURL_INFO, $response->request->curlInfo );
        $this->assertEquals(self::CURL_OPTIONS, $response->request->curlOptions );
    }

    /**
     * Вспомогательный метод для установки объектов
     *
     * @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException|InvalidEndpointException|ParamNotFoundException|InvalidMethodException|
     * @throws InvalidHeaderException|InvalidHostException|RequestCompleteException|InvalidRequestException
     * 
     * @tag #test #response #setup #objects
     */
    private function setupObjects(): void
    {
        $this->operator = new Operator(PostmanEcho::HOST,[
            RequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
            ]
        ]);
        $this->operator->disableSSL();

        $this->request = $this->operator
            ->constructRequest(Method::GET, PostmanEcho::ENDPOINT_GET, [
                RequestInterface::SETUP_DATA => PostmanEcho::DATA,
        ]);

        $this->response = $this->operator->send($this->request);
    }

    /**
     * Проверка замены данных объекта класса `Response`
     *      Тест ожидает, что данные объекта будут заменены на новые
     *
     * Source: @see Response::getHttpCode()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testReplace
     *
     * @tag #test #response #getHttpCode
     */
    public function testReplace()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $request);

        $response = new Response(self::CONTENT, self::HTTP_CODE_OK, $request );
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(self::HTTP_CODE_OK, $response->httpCode );
        $this->assertEquals(self::CONTENT, $response->content);

        $newContent = 'new content';
        $newHttpCode = 777;

        $response->replace(ResponseInterface::CONTENT, $newContent);
        $response->replace(ResponseInterface::HTTP_CODE, $newHttpCode);

        $this->assertEquals( $newHttpCode, $response->httpCode );
        $this->assertEquals( $newContent, $response->content );
    }

    /**
     * Проверка метода asArray объекта класса `Response`
     *      Тест ожидает на выходе массив сформированный из данных JSON ответа
     *
     * Source: @see Response::asArray()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testAsArray
     *
     * @tag #test #response #asArray
     */
    public function testAsArray()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $request);

        $response = new Response( self::CONTENT, self::HTTP_CODE_OK, $request );
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(self::CONTENT, $response->content );

        $response->asArray();

        $this->assertEquals( json_decode(self::CONTENT, true), $response->content, "Ожидается, что контент будет преобразован в массив");

        $this->assertIsArray($response->content, "Ожидается, что контент будет преобразован в массив");
    }

    /**
     * Проверка метода getErrors объекта класса `Response`
     *      Тест ожидает, что в начале у объекта нет ошибок, далее добавляет ошибку и проверяет,
     *      что readOnly свойство `errors` содержит одну ошибку
     *
     * Source: @see Response::getErrors()
     * Source: @see Response::addError()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testGetErrors
     *
     * @tag #test #response #getErrors
     */
    public function testGetErrors()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $request);

        $response = new Response(self::CONTENT, self::HTTP_CODE_OK, $request );
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEmpty($response->errors);

        $response->addError('test');

        $this->assertCount(1, $response->errors, "Ожидается, что в массиве ошибок будет одна ошибка");

        $request->setupStatusComplete();

        $response->addError('test2');

        $this->assertCount(1, $response->errors, "Ожидается, что ошибки не добавятся после завершения запроса");
    }

    /**
     * Проверка метода validate объекта класса `Response`
     *      Тест ожидает, что валидация при отсутствии ошибок пройдет успешно и вернет true
     *      После добавления ошибки валидация вернет false
     *
     *
     * Source: @see Response::validate()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testValidate
     *
     * @tag #test #response #validate
     */
    public function testValidate()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $request);

        $response = new Response(self::CONTENT, self::HTTP_CODE_OK, $request );
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->validate(), "Ожидается, что валидация пройдет успешно");

        $response->addError('test');

        $this->assertFalse($response->validate(), "Ожидается, что валидация не пройдет после добавления ошибки");
    }

    /**
     * Проверка метода setupData объекта класса `Response`
     *      Тест ожидает, что данные объекта будут заменены на новые
     *
     * Source: @see Response::getData()
     * Source: @see Response::setupData()
     * Source: @see Response::convertDataToArray()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testSetupData
     *
     * @tag #test #response #setupData
     */
    public function testSetupData()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $request);

        $content = 'testSetupData Content';

        $response = new Response($content, self::HTTP_CODE_OK, $request );
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals($content, $response->content, "Ожидается, что контент будет равен '$content'");
    }

    /**
     * Проверка метода setupHttpCode объекта класса `Response`
     *      Тест ожидает, что код ответа будет заменен на новый
     *
     * Source: @see Response::getHttpCode()
     * Source: @see Response::setupHttpCode()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testSetupHttpCode
     *
     * @tag #test #response #setupHttpCode
     */
    public function testSetupHttpCode()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $request);

        $newHttpCode = 777;

        $response = new Response(self::CONTENT, $newHttpCode, $request );
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals( $newHttpCode, $response->httpCode, "Ожидается, что код ответа будет равен $newHttpCode");
    }

    /**
     * Проверка метода setupRequest объекта класса `Response`
     *      Тест ожидает, что объект запроса будет заменен на новый
     *
     * Source: @see Response::getRequest()
     * Source: @see Response::setupRequest()
     *
     * @return void
     *
     * @cli vendor/bin/phpunit tests/core/ResponseTest.php --testdox --filter testSetupRequest
     *
     * @tag #test #response #setupRequest
     */
    public function testSetupRequest()
    {
        $request = new Request(PostmanEcho::ENDPOINT_GET, self::PARAMS);
        $this->assertInstanceOf(Request::class, $request);

        $response = new Response(self::CONTENT, self::HTTP_CODE_OK, $request );
        $this->assertInstanceOf(Response::class, $response);

        $originalRequestParams = json_encode($request->params);
        $responseRequestParams = json_encode($response->request->params);

        $this->assertEquals( $originalRequestParams, $responseRequestParams, "Ожидается, что параметры запроса будут равны");
    }
}