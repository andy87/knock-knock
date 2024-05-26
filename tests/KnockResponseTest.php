<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnock
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace tests;

use Exception;
use tests\core\PostmanEcho;
use tests\core\UnitTestCore;
use andy87\knock_knock\lib\LibKnockMethod;
use andy87\knock_knock\core\{ KnockKnock, KnockRequest, KnockResponse };
use andy87\knock_knock\interfaces\{ KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockResponseTest
 *
 * Тесты для методов класса KnockResponse
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/KnockResponseTest.php --testdox
 *
 * @tag #test #knockResponse
 */
class KnockResponseTest extends UnitTestCore
{
    /** @var KnockKnock $knocKnock */
    private KnockKnock $knockKnock;


    /** @var KnockRequest $knockRequest */
    private KnockRequest $knockRequest;


    /** @var KnockResponse $knockResponse */
    private KnockResponse $knockResponse;



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
     * Проверка создания объекта класса `KnockResponse`
     *      Тест ожидает, что объект будет создан
     *
     * @see KnockResponse::__construct()
     *
     * @throws Exception
     *
     * @return void
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testConstructor
     *
     * @tag #test #knockResponse #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(KnockKnock::class, $this->knockKnock );
        $this->assertInstanceOf(KnockRequest::class, $this->knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $this->knockResponse );
    }

    /**
     * Проверка геттеров объекта класса `KnockResponse`
     *      Тест ожидает, что геттеры вернут ожидаемые значения
     *
     * Source: @see KnockResponse::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testMagicGet
     *
     * @tag #test #knockResponse #getter
     */
    public function testMagicGet()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, [
            KnockRequestInterface::SETUP_DATA => PostmanEcho::DATA,
            KnockRequestInterface::SETUP_CURL_INFO => self::CURL_INFO,
            KnockRequestInterface::SETUP_CURL_OPTIONS => self::CURL_OPTIONS,
        ]);

        $knockResponse = new KnockResponse(self::CONTENT, self::HTTP_CODE_OK, $knockRequest );

        $this->assertEquals(self::HTTP_CODE_OK, $knockResponse->httpCode );
        $this->assertEquals(self::CONTENT, $knockResponse->content );

        $jsonOriginal = json_encode($knockRequest->params);
        $jsonResponse = json_encode($knockResponse->request->params);
        $this->assertEquals( $jsonOriginal, $jsonResponse );

        $this->assertEquals(self::CURL_INFO, $knockResponse->request->curlInfo );
        $this->assertEquals(self::CURL_OPTIONS, $knockResponse->request->curlOptions );
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
        $this->knockKnock = new KnockKnock(PostmanEcho::HOST,[
            KnockRequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
            ]
        ]);
        $this->knockKnock->disableSSL();

        $this->knockRequest = $this->knockKnock
            ->constructRequest(LibKnockMethod::GET, PostmanEcho::ENDPOINT_GET, [
                KnockRequestInterface::SETUP_DATA => PostmanEcho::DATA,
        ]);

        $this->knockKnock->setupRequest($this->knockRequest);

        $this->knockResponse = $this->knockKnock->send();
    }

    /**
     * Проверка замены данных объекта класса `KnockResponse`
     *      Тест ожидает, что данные объекта будут заменены на новые
     *
     * Source: @see KnockResponse::getHttpCode()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testReplace
     *
     * @tag #test #knockResponse #getHttpCode
     */
    public function testReplace()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $knockResponse = new KnockResponse(self::CONTENT, self::HTTP_CODE_OK, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $this->assertEquals(self::HTTP_CODE_OK, $knockResponse->httpCode );
        $this->assertEquals(self::CONTENT, $knockResponse->content);

        $newContent = 'new content';
        $newHttpCode = 777;

        $knockResponse->replace(KnockResponseInterface::CONTENT, $newContent);
        $knockResponse->replace(KnockResponseInterface::HTTP_CODE, $newHttpCode);

        $this->assertEquals( $newHttpCode, $knockResponse->httpCode );
        $this->assertEquals( $newContent, $knockResponse->content );
    }

    /**
     * Проверка метода asArray объекта класса `KnockResponse`
     *      Тест ожидает на выходе массив сформированный из данных JSON ответа
     *
     * Source: @see KnockResponse::asArray()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testAsArray
     *
     * @tag #test #knockResponse #asArray
     */
    public function testAsArray()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $knockResponse = new KnockResponse( self::CONTENT, self::HTTP_CODE_OK, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $this->assertEquals(self::CONTENT, $knockResponse->content );

        $knockResponse->asArray();

        $this->assertEquals( json_decode(self::CONTENT, true), $knockResponse->content, "Ожидается, что контент будет преобразован в массив");

        $this->assertIsArray($knockResponse->content, "Ожидается, что контент будет преобразован в массив");
    }

    /**
     * Проверка метода getErrors объекта класса `KnockResponse`
     *      Тест ожидает, что в начале у объекта нет ошибок, далее добавляет ошибку и проверяет,
     *      что readOnly свойство `errors` содержит одну ошибку
     *
     * Source: @see KnockResponse::getErrors()
     * Source: @see KnockResponse::addError()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testGetErrors
     *
     * @tag #test #knockResponse #getErrors
     */
    public function testGetErrors()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $knockResponse = new KnockResponse(self::CONTENT, self::HTTP_CODE_OK, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $this->assertEmpty($knockResponse->errors);

        $knockResponse->addError('test');

        $this->assertCount(1, $knockResponse->errors, "Ожидается, что в массиве ошибок будет одна ошибка");

        $knockRequest->setupStatusComplete();

        $knockResponse->addError('test2');

        $this->assertCount(1, $knockResponse->errors, "Ожидается, что ошибки не добавятся после завершения запроса");
    }

    /**
     * Проверка метода validate объекта класса `KnockResponse`
     *      Тест ожидает, что валидация при отсутствии ошибок пройдет успешно и вернет true
     *      После добавления ошибки валидация вернет false
     *
     *
     * Source: @see KnockResponse::validate()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testValidate
     *
     * @tag #test #knockResponse #validate
     */
    public function testValidate()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $knockResponse = new KnockResponse(self::CONTENT, self::HTTP_CODE_OK, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $this->assertTrue($knockResponse->validate(), "Ожидается, что валидация пройдет успешно");

        $knockResponse->addError('test');

        $this->assertFalse($knockResponse->validate(), "Ожидается, что валидация не пройдет после добавления ошибки");
    }

    /**
     * Проверка метода setupData объекта класса `KnockResponse`
     *      Тест ожидает, что данные объекта будут заменены на новые
     *
     * Source: @see KnockResponse::getData()
     * Source: @see KnockResponse::setupData()
     * Source: @see KnockResponse::convertDataToArray()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testSetupData
     *
     * @tag #test #knockResponse #setupData
     */
    public function testSetupData()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $content = 'testSetupData Content';

        $knockResponse = new KnockResponse($content, self::HTTP_CODE_OK, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $this->assertEquals($content, $knockResponse->content, "Ожидается, что контент будет равен '$content'");
    }

    /**
     * Проверка метода setupHttpCode объекта класса `KnockResponse`
     *      Тест ожидает, что код ответа будет заменен на новый
     *
     * Source: @see KnockResponse::getHttpCode()
     * Source: @see KnockResponse::setupHttpCode()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testSetupHttpCode
     *
     * @tag #test #knockResponse #setupHttpCode
     */
    public function testSetupHttpCode()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $newHttpCode = 777;

        $knockResponse = new KnockResponse(self::CONTENT, $newHttpCode, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $this->assertEquals( $newHttpCode, $knockResponse->httpCode, "Ожидается, что код ответа будет равен $newHttpCode");
    }

    /**
     * Проверка метода setupRequest объекта класса `KnockResponse`
     *      Тест ожидает, что объект запроса будет заменен на новый
     *
     * Source: @see KnockResponse::getRequest()
     * Source: @see KnockResponse::setupRequest()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockResponseTest.php --filter testSetupRequest
     *
     * @tag #test #knockResponse #setupRequest
     */
    public function testSetupRequest()
    {
        $knockRequest = new KnockRequest(PostmanEcho::ENDPOINT_GET, self::PARAMS);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest);

        $knockResponse = new KnockResponse(self::CONTENT, self::HTTP_CODE_OK, $knockRequest );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse);

        $originalRequestParams = json_encode($knockRequest->params);
        $responseRequestParams = json_encode($knockResponse->request->params);

        $this->assertEquals( $originalRequestParams, $responseRequestParams, "Ожидается, что параметры запроса будут равны");
    }
}