<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса Handler
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\core;

use andy87\knock_knock\core\{Handler, Request, Response};
use andy87\knock_knock\interfaces\{RequestInterface, ResponseInterface};
use andy87\knock_knock\lib\Method;
use andy87\knock_knock\tests\helpers\{PostmanEcho, UnitTestCore};
use Exception;

/**
 * Class ResponseTest
 *
 * Тесты для методов класса Response
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox
 *
 * @tag #test #Response
 */
class ResponseTest extends UnitTestCore
{
    /** @var Handler $knocKnock */
    private Handler $Handler;


    /** @var Request $Request */
    private Request $Request;


    /** @var Response $Response */
    private Response $Response;



    /**
     * Установки
     *
     * @throws Exception
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
     * @throws Exception
     *
     * @return void
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testConstructor
     *
     * @tag #test #Response #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Handler::class, $this->Handler );
        $this->assertInstanceOf(Request::class, $this->Request );
        $this->assertInstanceOf(Response::class, $this->Response );
    }

    /**
     * Проверка геттеров объекта класса `Response`
     *      Тест ожидает, что геттеры вернут ожидаемые значения
     *
     * Source: @see Response::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testMagicGet
     *
     * @tag #test #Response #getter
     */
    public function testMagicGet()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, [
            RequestInterface::SETUP_DATA => PostmanEcho::DATA,
            RequestInterface::SETUP_CURL_INFO => self::CURL_INFO,
            RequestInterface::SETUP_CURL_OPTIONS => self::CURL_OPTIONS,
        ]);

        $Response = new Response(self::CONTENT, self::HTTP_CODE_OK, $Request );

        $this->assertEquals(self::HTTP_CODE_OK, $Response->httpCode );
        $this->assertEquals(self::CONTENT, $Response->content );

        $jsonOriginal = json_encode($Request->params);
        $jsonResponse = json_encode($Response->request->params);
        $this->assertEquals( $jsonOriginal, $jsonResponse );

        $this->assertEquals(self::CURL_INFO, $Response->request->curlInfo );
        $this->assertEquals(self::CURL_OPTIONS, $Response->request->curlOptions );
    }

    /**
     * Вспомогательный метод для установки объектов
     *
     * @return void
     *
     * @throws Exception
     */
    private function setupObjects(): void
    {
        $this->Handler = new Handler(PostmanEcho::HOST,[
            RequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
            ]
        ]);
        $this->Handler->disableSSL();

        $this->Request = $this->Handler
            ->constructRequest(Method::GET, PostmanEcho::ENDPOINT_GET, [
                RequestInterface::SETUP_DATA => PostmanEcho::DATA,
        ]);

        $this->Handler->setupRequest($this->Request);

        $this->Response = $this->Handler->send();
    }

    /**
     * Проверка замены данных объекта класса `Response`
     *      Тест ожидает, что данные объекта будут заменены на новые
     *
     * Source: @see Response::getHttpCode()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testReplace
     *
     * @tag #test #Response #getHttpCode
     */
    public function testReplace()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $Request);

        $Response = new Response(self::CONTENT, self::HTTP_CODE_OK, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $this->assertEquals(self::HTTP_CODE_OK, $Response->httpCode );
        $this->assertEquals(self::CONTENT, $Response->content);

        $newContent = 'new content';
        $newHttpCode = 777;

        $Response->replace(ResponseInterface::CONTENT, $newContent);
        $Response->replace(ResponseInterface::HTTP_CODE, $newHttpCode);

        $this->assertEquals( $newHttpCode, $Response->httpCode );
        $this->assertEquals( $newContent, $Response->content );
    }

    /**
     * Проверка метода asArray объекта класса `Response`
     *      Тест ожидает на выходе массив сформированный из данных JSON ответа
     *
     * Source: @see Response::asArray()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testAsArray
     *
     * @tag #test #Response #asArray
     */
    public function testAsArray()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $Request);

        $Response = new Response( self::CONTENT, self::HTTP_CODE_OK, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $this->assertEquals(self::CONTENT, $Response->content );

        $Response->asArray();

        $this->assertEquals( json_decode(self::CONTENT, true), $Response->content, "Ожидается, что контент будет преобразован в массив");

        $this->assertIsArray($Response->content, "Ожидается, что контент будет преобразован в массив");
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
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testGetErrors
     *
     * @tag #test #Response #getErrors
     */
    public function testGetErrors()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $Request);

        $Response = new Response(self::CONTENT, self::HTTP_CODE_OK, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $this->assertEmpty($Response->errors);

        $Response->addError('test');

        $this->assertCount(1, $Response->errors, "Ожидается, что в массиве ошибок будет одна ошибка");

        $Request->setupStatusComplete();

        $Response->addError('test2');

        $this->assertCount(1, $Response->errors, "Ожидается, что ошибки не добавятся после завершения запроса");
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
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testValidate
     *
     * @tag #test #Response #validate
     */
    public function testValidate()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $Request);

        $Response = new Response(self::CONTENT, self::HTTP_CODE_OK, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $this->assertTrue($Response->validate(), "Ожидается, что валидация пройдет успешно");

        $Response->addError('test');

        $this->assertFalse($Response->validate(), "Ожидается, что валидация не пройдет после добавления ошибки");
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
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testSetupData
     *
     * @tag #test #Response #setupData
     */
    public function testSetupData()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $Request);

        $content = 'testSetupData Content';

        $Response = new Response($content, self::HTTP_CODE_OK, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $this->assertEquals($content, $Response->content, "Ожидается, что контент будет равен '$content'");
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
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testSetupHttpCode
     *
     * @tag #test #Response #setupHttpCode
     */
    public function testSetupHttpCode()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(Request::class, $Request);

        $newHttpCode = 777;

        $Response = new Response(self::CONTENT, $newHttpCode, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $this->assertEquals( $newHttpCode, $Response->httpCode, "Ожидается, что код ответа будет равен $newHttpCode");
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
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/ResponseTest.php --testdox --filter testSetupRequest
     *
     * @tag #test #Response #setupRequest
     */
    public function testSetupRequest()
    {
        $Request = new Request(PostmanEcho::ENDPOINT_GET, self::PARAMS);
        $this->assertInstanceOf(Request::class, $Request);

        $Response = new Response(self::CONTENT, self::HTTP_CODE_OK, $Request );
        $this->assertInstanceOf(Response::class, $Response);

        $originalRequestParams = json_encode($Request->params);
        $responseRequestParams = json_encode($Response->request->params);

        $this->assertEquals( $originalRequestParams, $responseRequestParams, "Ожидается, что параметры запроса будут равны");
    }
}