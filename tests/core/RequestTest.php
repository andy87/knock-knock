<?php declare(strict_types=1);

/**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса Handler
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

namespace andy87\knock_knock\tests\core;

use andy87\knock_knock\core\{Operator, Request};
use andy87\knock_knock\tests\helpers\UnitTestCore;
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\lib\{ContentType, Method};
use andy87\knock_knock\interfaces\ResponseInterface;
use andy87\knock_knock\exception\{InvalidHostException,
    InvalidEndpointException,
    ParamNotFoundException,
    ParamUpdateException};
use andy87\knock_knock\exception\{operator\InvalidMethodException,
    request\InvalidHeaderException,
    request\InvalidProtocolException,
    request\StatusNotFoundException
};

/**
 * Class RequestTest
 *
 * Тесты для методов класса Request
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox
 *
 * @tag #test #Request
 */
class RequestTest extends UnitTestCore
{
    /** @var Request $request */
    private Request $request;


    /**
     * Установки
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @tag #test #Request #setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->request = $this->getRequest(self::ENDPOINT, self::PARAMS);
    }

    /**
     * Проверка создания объекта класса `Request`
     *      Тест ожидает, что объект будет создан
     *
     * Source: @return void
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testConstructor
     *
     * @tag #test #Request #constructor
     * @see Request::__construct()
     *
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Request::class, $this->request);
    }

    /**
     * Проверка доступа к ReadOnly свойствам объекта.
     *      Тест ожидает, что свойства будут доступны для чтения.
     *
     * Source: @return void
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testMagicGet
     *
     * @tag #test #Request #magic #get
     * @see Request::__get()
     *
     */
    public function testMagicGet(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals(RequestInterface::STATUS_PREPARE, $request->status_id);
        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PREPARE],
            $request->statusLabel
        );

        $this->assertEqualsRequestParams($request);

        $this->assertEquals(self::PARAMS, $request->params);
    }

    /**
     * Вспомогательный метод для проверки параметров запроса
     *
     * @param Request $request
     *
     * @return void
     *
     * @tag #test #Request #helper #requestParams
     */
    private function assertEqualsRequestParams(Request $request): void
    {
        $this->assertEquals(self::PROTOCOL, $request->protocol);
        $this->assertEquals(self::HOST, $request->host);
        $this->assertEquals(self::ENDPOINT, $request->endpoint);

        $this->assertEquals(self::METHOD, $request->method);
        $this->assertEquals(self::HEADERS, $request->headers);
        $this->assertEquals(self::CONTENT_TYPE, $request->contentType);

        $this->assertEquals(self::DATA, $request->data);

        $this->assertEquals(self::CURL_OPTIONS, $request->curlOptions);
        $this->assertEquals(self::CURL_INFO, $request->curlInfo);
    }


    /**
     * Проверка формирования URL для запроса методом GET.
     *      Тест ожидает, что установленный URL будет доступен в свойстве `url`
     *      с добавлением HttpBuildQuery данных в строку запроса.
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testConstructUrlOnGet
     *
     * @tag #test #Request #constructUrl #get
     * @see Request::constructUrl()
     *
     */
    public function testConstructUrlOnGet(): void
    {
        $request = $this->request->setMethod(Method::GET);
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals(self::PROTOCOL, $request->protocol);
        $this->assertEquals(self::HOST, $request->host);
        $this->assertEquals(self::ENDPOINT, $request->endpoint);

        $url = self::PROTOCOL . '://' . self::HOST . self::ENDPOINT
            . '?' . http_build_query($request->data);

        $this->assertEquals($request->url, $url);
    }

    /**
     * Проверка формирования URL для запроса методом POST.
     *      Тест ожидает, что установленный URL будет доступен в свойстве `url`
     *      без добавления данных в строку запроса.
     *
     * Source: @return void
     *
     * @throws InvalidEndpointException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testConstructUrlOnPost
     *
     * @tag #test #Request #constructUrl #post
     * @see Request::constructUrl()
     *
     */
    public function testConstructUrlOnPost(): void
    {
        $request = (new Operator(self::HOST))
            ->constructRequest(
                Method::POST,
                self::ENDPOINT
            );

        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals(self::PROTOCOL, $request->protocol);
        $this->assertEquals(self::HOST, $request->host);
        $this->assertEquals(self::ENDPOINT, $request->endpoint);

        $url = self::PROTOCOL . '://' . self::HOST . self::ENDPOINT;


        $this->assertEquals($request->url, $url);
    }

    /**
     * Проверка подготовки `endpoint` для запроса методом GET.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *      с добавлением HttpBuildQuery данных в строку запроса.
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testPrepareEndpointOnGet
     *
     * @tag #test #Request #prepare #endpoint #get
     * @see Request::prepareEndpoint()
     *
     */
    public function testPrepareEndpointOnGet(): void
    {
        $request = $this->request->setMethod(Method::GET);
        $this->assertInstanceOf(Request::class, $request);


        $endpoint = 'newEndpoint';
        $request->setEndpoint($endpoint);
        $request->setData(self::DATA);

        $request->prepareEndpoint();

        $endpoint = 'newEndpoint?' . http_build_query(self::DATA);

        $this->assertEquals($endpoint, $request->endpoint);
    }

    /**
     * Проверка подготовки `endpoint` для запроса методом POST.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *      без добавления данных в строку запроса.
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testPrepareEndpointOnPost
     *
     * @tag #test #Request #prepare #endpoint #post
     * @see Request::prepareEndpoint()
     *
     */
    public function testPrepareEndpointOnPost(): void
    {
        $request = $this->request->setMethod(Method::POST);
        $this->assertInstanceOf(Request::class, $request);

        $endpoint = 'newEndpoint';
        $request->setEndpoint($endpoint);
        $request->setData(self::DATA);

        $request->prepareEndpoint();

        $this->assertEquals($endpoint, $request->endpoint);
    }

    /**
     * Проверка установки протокола для запроса.
     *      Тест ожидает, что установленный протокол будет доступен в свойстве `protocol`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetProtocol
     *
     * @tag #test #Request #set #protocol
     * @see Request::setProtocol()
     *
     */
    public function testSetProtocol(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $protocol = 'wss';
        $request->setProtocol($protocol);
        $this->assertEquals($protocol, $request->protocol);
    }

    /**
     * Проверка установки `host` для запроса.
     *      Тест ожидает, что установленный `host` будет доступен в свойстве `host`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetHost
     *
     * @tag #test #Request #set #host
     * @see Request::setHost()
     *
     */
    public function testSetHost(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $host = 'newHost';
        $request->setHost($host);
        $this->assertEquals($host, $request->host);
    }

    /**
     * Проверка установки `endpoint` для запроса.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetEndpoint
     *
     * @tag #test #Request #set #endpoint
     * @see Request::setEndpoint()
     *
     */
    public function testSetEndpoint(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $endpoint = 'newEndpoint';
        $request->setEndpoint($endpoint);
        $this->assertEquals($endpoint, $request->endpoint);
    }

    /**
     * Проверка установки метода запроса.
     *      Тест ожидает, что установленный метод будет доступен в свойстве `method`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetMethod
     *
     * @tag #test #Request #set #method
     * @see Request::setMethod()
     *
     */
    public function testSetMethod(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $request->setMethod(Method::PATCH);
        $this->assertEquals(Method::PATCH, $request->method);
    }

    /**
     * Проверка установки одного заголовка к запросу.
     *      Тест ожидает, что установленный заголовок будет доступен в свойстве `headers`
     *
     * Source: @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetHeader
     *
     * @tag #test #Request #set #headers
     * @see Request::setHeader()
     *
     */
    public function testSetHeader(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $headerKey = 'newHeaderKey';
        $headerValue = 'newHeaderValue';

        $request->setHeader($headerKey, $headerValue);

        $this->assertEquals($headerValue, $request->headers[$headerKey]);
    }

    /**
     * Проверка добавления заголовков к запросу.
     *      Тест ожидает, что добавленные заголовки будут доступны в свойстве `headers`
     *
     * Source: @return void
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testAddHeaders
     *
     * @tag #test #Request #headers #add
     * @see Request::addHeaders()
     *
     */
    public function testAddHeaders(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $headers = [
            'a' => 'c',
            'b' => 'd',
        ];
        $request->addHeaders($headers);

        $this->assertEquals($headers['a'], $request->headers['a']);
        $this->assertEquals($headers['b'], $request->headers['b']);
    }

    /**
     * Проверка установки `contentType` для запроса.
     *      Тест ожидает, что установленный тип контента будет доступен в свойстве `contentType`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetContentType
     *
     * @tag #test #Request #set #contentType
     * @see Request::setContentType()
     *
     */
    public function testSetContentType(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $request->setContentType(ContentType::MULTIPART);

        $this->assertEquals(ContentType::MULTIPART, $request->contentType);
    }

    /**
     * Проверка установки данных для запроса.
     *      Тест ожидает, что установленные данные будут доступны в свойстве `data`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetData
     *
     * @tag #test #Request #set #data
     * @see Request::setData()
     *
     */
    public function testSetData(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $data = ['newDataKey' => 'newDataValue'];

        $request->setData($data);

        $this->assertEquals($data, $request->data);
    }

    /**
     * Проверка установки опций для запроса.
     *      Тест ожидает, что установленные опции будут доступны в свойстве `curlOptions`
     *
     * Source: @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetCurlOptions
     *
     * @tag #test #Request #set #curlOptions
     * @see Request::setCurlOptions()
     *
     */
    public function testSetCurlOptions(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $curlOptions = [CURLOPT_TIMEOUT => 60];

        $request->setCurlOptions($curlOptions);

        $this->assertEquals($curlOptions, $request->curlOptions);
    }

    /**
     * Проверка добавления опций к запросу.
     *      Тест ожидает, что добавленные опции будут доступны в свойстве `curlOptions`
     *
     * Source: @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testAddCurlOptions
     *
     * @tag #test #Request #add #curlOptions
     * @see Request::addCurlOptions()
     *
     */
    public function testAddCurlOptions(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $curlOptions = [
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30
        ];

        $request->addCurlOptions($curlOptions);

        $this->assertEquals($curlOptions, $request->curlOptions);
    }

    /**
     * Проверка установки информации о запросе.
     *      Тест ожидает, что установленные значения будут доступны в свойстве `curlInfo`
     *
     * Source: @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetCurlInfo
     *
     * @tag #test #Request #set #curlInfo
     * @see Request::setCurlInfo()
     *
     */
    public function testSetCurlInfo(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $curlInfo = [
            CURLINFO_CONTENT_TYPE,
            CURLINFO_HEADER_SIZE,
            CURLINFO_TOTAL_TIME
        ];

        $request->setCurlInfo($curlInfo);

        $this->assertEquals($curlInfo, $request->curlInfo);
    }

    //testSetFakeResponse

    /**
     * Проверка установки фейкового ответа.
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetFakeResponse
     *
     * @tag #test #Request #set #fakeResponse
     * @see Request::setFakeResponse()
     *
     */
    public function testSetFakeResponse(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $fakeResponse = [
            ResponseInterface::HTTP_CODE => ResponseInterface::OK,
            ResponseInterface::CONTENT => __METHOD__,
        ];

        $request->setFakeResponse($fakeResponse);

        $this->assertEquals($fakeResponse, $request->fakeResponse);

        $request->setupStatusComplete();

        $this->expectException(ParamUpdateException::class);
        $request->setFakeResponse($fakeResponse);
    }

    /**
     * Проверка добавления ошибки в массив ошибок запроса.
     *      Тест ожидает, что добавленная ошибка будет доступна по ключу.
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testAddError
     *
     * @tag #test #Request #add #error
     * @see Request::addError()
     *
     */
    public function testAddError(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $errorKey = 'errorKey';
        $errorText = 'errorText';

        $request->addError($errorText, $errorKey);

        $this->assertEquals($errorText, $request->errors[$errorKey]);
    }

    /**
     * Проверка назначения запросу статуса - "в обработке".
     *      Тест ожидает актуальные значения в свойствах `status_id` и `statusLabel`
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetupStatusProcessing
     *
     * @tag #test #Request #status #processing
     * @see Request::setupStatusProcessing()
     *
     */
    public function testSetupStatusProcessing(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $request->setupStatusProcessing();

        $this->assertEquals(RequestInterface::STATUS_PROCESSING, $request->status_id);
        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PROCESSING],
            $request->statusLabel
        );
    }

    /**
     * Проверка назначения запросу статуса - "завершён".
     *      Тест ожидает актуальные значения в свойствах `status_id` и `statusLabel`
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetupStatusComplete
     *
     * @tag #test #Request #status #complete
     * @see Request::setupStatusComplete()
     *
     */
    public function testSetupStatusComplete(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $request->setupStatusComplete();

        $this->assertEquals(RequestInterface::STATUS_COMPLETE, $request->status_id);
        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_COMPLETE],
            $request->statusLabel
        );
    }

    /**
     * Проверка, что запрос завершён.
     *      Тест ожидает `false` на проверку значения статуса = `STATUS_COMPLETE` при новом, созданном объекте
     *      и `true` после изменения статуса на `STATUS_COMPLETE`
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testStatusIsComplete
     *
     * @tag #test #Request #status #complete
     * @see Request::statusIsComplete()
     *
     */
    public function testStatusIsComplete(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertFalse($request->statusIsComplete());

        $request->setupStatusComplete();

        $this->assertTrue($request->statusIsComplete());
    }

    /**
     * Проверка установленного статуса запроса - "подготовка".
     *      Тест ожидает у созданного объекта `Request` статус `STATUS_PREPARE`,
     *      а после изменения статуса на `STATUS_COMPLETE` ожидает `false`
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testStatusIsPrepare
     *
     * @tag #test #Request #status #prepare
     * @see Request::statusIsPrepare()
     *
     */
    public function testStatusIsPrepare(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertTrue($request->statusIsPrepare());

        $request->setupStatusComplete();

        $this->assertFalse($request->statusIsPrepare());
    }

    /**
     * Проверка данных указывающих на ОТКЛЮЧЕНИЕ проверки SSL
     *      Тест ожидает определённые значения
     *      в свойствах `curlOptions[CURLOPT_SSL_VERIFYPEER]` и `curlOptions[CURLOPT_SSL_VERIFYHOST]`
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testDisableSSL
     *
     * @tag #test #Request #ssl #disable
     * @see Request::disableSSL()
     *
     */
    public function testDisableSSL(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertFalse(isset($request->curlOptions[CURLOPT_SSL_VERIFYPEER]));
        $this->assertFalse(isset($request->curlOptions[CURLOPT_SSL_VERIFYHOST]));

        $request->disableSSL();

        $this->assertFalse($request->curlOptions[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals(0, $request->curlOptions[CURLOPT_SSL_VERIFYHOST]);
    }

    /**
     * Проверка данных указывающих на ВКЛЮЧЕНИЕ проверки SSL
     *      Тест ожидает определённые значения
     *      в свойствах `curlOptions[CURLOPT_SSL_VERIFYPEER]` и `curlOptions[CURLOPT_SSL_VERIFYHOST]`
     *
     * Source: @return void
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testEnableSSL
     *
     * @tag #test #Request #ssl #enable
     * @see Request::enableSSL()
     *
     */
    public function testEnableSSL(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertFalse(isset($request->curlOptions[CURLOPT_SSL_VERIFYPEER]));
        $this->assertFalse(isset($request->curlOptions[CURLOPT_SSL_VERIFYHOST]));

        $request->enableSSL();

        $this->assertTrue($request->curlOptions[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals(2, $request->curlOptions[CURLOPT_SSL_VERIFYHOST]);
    }

    /**
     * Проверка, невозможности назначения свойств запросу который уже выполнен.
     *      Тест ожидает `Exception` потому что запрос уже завершен(статус `STATUS_COMPLETE`)
     *      и нельзя изменить параметры запроса.
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testLimiterIsComplete
     *
     * @tag #test #Request #limiter #status #complete
     * @see Request::limiterIsComplete()
     *
     */
    public function testLimiterIsComplete(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $request->setupStatusComplete();

        $this->expectException(ParamUpdateException::class);
        $request->setProtocol('newProtocol');
        $request->setHost('newHost');
        $request->setEndpoint('newEndpoint');
        $request->setMethod(Method::PATCH);
        $request->setContentType(ContentType::MULTIPART);
        $request->setHeader('newHeaderKey', 'newHeaderValue');
        $request->setData(['newDataKey' => 'newDataValue']);
        $request->setCurlOptions([CURLOPT_TIMEOUT => 60]);
        $request->setCurlInfo([CURLINFO_CONTENT_TYPE]);
    }

    /**
     * Проверка подготовки `domain` для запроса.
     *      Тест ожидает, что собранный `domain` будет доступен в свойстве `domain`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHostException|InvalidEndpointException|InvalidProtocolException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testPrepareHost
     *
     * @tag #test #Request #prepare #host
     * @see Request::prepareHost()
     *
     */
    public function testPrepareHost(): void
    {
        $protocol = 'http';
        $host = 'first.host';

        $operator = new Operator("$protocol://$host");
        $this->assertInstanceOf(Operator::class, $operator);

        $this->assertEquals($protocol, $operator->commonRequest->protocol);
        $this->assertEquals($host, $operator->commonRequest->host);

        $protocol = 'wss';
        $host = 'second.host';
        $operator->commonRequest->setHost("$protocol://$host");

        $this->assertEquals($protocol, $operator->commonRequest->protocol);
        $this->assertEquals($host, $operator->commonRequest->host);

        $protocol = 'https';
        $host = 'next.host';
        $endpoint = 'endpoint';
        $operator->commonRequest->setProtocol($protocol);
        $operator->commonRequest->setHost($host);
        $operator->commonRequest->setEndpoint($endpoint);
        $operator->commonRequest->constructUrl();

        $this->assertEquals($protocol, $operator->commonRequest->protocol);
        $this->assertEquals($host, $operator->commonRequest->host);
    }

    /**
     * Проверка установки параметров запроса из массива.
     *      Тест ожидает, что установленные параметры будут доступны в свойствах объекта
     *
     * Source: @return void
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetupParamsFromArray
     *
     * @tag #test #Request #setup #params
     * @see Request::setupParamsFromArray()
     *
     */
    public function testSetupParamsFromArray(): void
    {
        $request = new Request(self::HOST, self::PARAMS);

        $this->assertInstanceOf(Request::class, $request);

        $this->assertEqualsRequestParams($request);
    }

    /**
     * Проверка установки параметров запроса в статусе "подготовка".
     *      Тест ожидает, что можно установить параметры запроса в статусе "подготовка"
     *      и они будут доступны в свойствах объекта.
     *      Тест ожидает, что нельзя установить параметры запроса в статусе "завершён"
     *      и будет выброшено исключение.
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testSetParamsOnStatusPrepare
     *
     * @tag #test #Request #set #prepare #params
     * @see Request::setParamsOnStatusPrepare()
     *
     */
    public function testSetParamsOnStatusPrepare(): void
    {
        $request = $this->getRequest(self::ENDPOINT, []);
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals(RequestInterface::STATUS_PREPARE, $request->status_id);

        $this->assertInstanceOf(Request::class, $request->setProtocol(self::PROTOCOL));
        $this->assertInstanceOf(Request::class, $request->setHost(self::HOST));
        $this->assertInstanceOf(Request::class, $request->setEndpoint(self::ENDPOINT));
        $this->assertInstanceOf(Request::class, $request->setMethod(self::METHOD));
        $this->assertInstanceOf(Request::class, $request->setContentType(self::CONTENT_TYPE));
        $this->assertInstanceOf(Request::class, $request->setData(self::DATA));
        $this->assertInstanceOf(Request::class, $request->setHeader('newHeaderKey', 'newHeaderValue'));
        $this->assertInstanceOf(Request::class, $request->addHeaders(self::HEADERS));
        $this->assertInstanceOf(Request::class, $request->setCurlOptions(self::CURL_OPTIONS));
        $this->assertInstanceOf(Request::class, $request->setCurlInfo(self::CURL_INFO));

        $request->setupStatusComplete();

        $this->assertEquals(RequestInterface::STATUS_COMPLETE, $request->status_id);

        $this->expectException(ParamUpdateException::class);
        $request->setProtocol(self::PROTOCOL);
    }

    /**
     * Проверка получения текстового статуса запроса.
     *      Тест ожидает, что текстовый статус запроса будет актуальным
     *
     * Source: @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testGetStatusLabel
     *
     * @tag #test #Request #status #label
     * @see Request::getStatusLabel()
     *
     */
    public function testGetStatusLabel(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PREPARE],
            $request->statusLabel
        );

        $request->setupStatusProcessing();

        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PROCESSING],
            $request->statusLabel
        );

        $request->setupStatusComplete();

        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_COMPLETE],
            $request->statusLabel
        );
    }

    /**
     * Проверка получения параметров запроса.
     *      Тест ожидает, что параметры запроса будут актуальными
     *
     * Source: @return void
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testGetParams
     *
     * @tag #test #Request #get #params
     * @see Request::getParams()
     *
     */
    public function testGetParams(): void
    {
        $request = new Request(self::HOST, self::PARAMS);
        $this->assertInstanceOf(Request::class, $request);

        $originalJson = json_encode(self::PARAMS);
        $resultJson = json_encode($request->params);

        $this->assertEquals($originalJson, $resultJson);
    }

    /**
     * Проверка получения ошибок запроса.
     *      Тест ожидает, ожидает получить из запроса все ошибки отправленные в него
     *
     * Source: @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testGetErrors
     *
     * @tag #test #Request #get #errors
     * @see Request::getErrors()
     *
     */
    public function testGetErrors(): void
    {
        $request = $this->request;
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals([], $request->errors);

        $errorKey = 'errorKey';
        $errorText = 'errorText';

        $request->addError($errorText, $errorKey);

        $this->assertEquals([$errorKey => $errorText], $request->errors);

        $request->addError('next Error');

        $this->assertCount(2, $request->errors);
    }

    /**
     * Проверка клонирования объекта запроса.
     *      Тест ожидает, что клонированный объект будет идентичен исходному, за исключением статуса запроса.
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/RequestTest.php --testdox --filter testClone
     *
     * @tag #test #Request #clone
     * @see Request::clone()
     *
     */
    public function testClone(): void
    {
        $request = new Request(self::HOST, self::PARAMS);
        $this->assertInstanceOf(Request::class, $request);

        $requestClone = $request->clone();

        $this->assertEquals($request->protocol, $requestClone->protocol, "у клона не совпадает `protocol` ");
        $this->assertEquals($request->host, $requestClone->host, "у клона не совпадает `host` ");
        $this->assertEquals($request->endpoint, $requestClone->endpoint, "у клона не совпадает `endpoint` ");

        $this->assertEquals($request->method, $requestClone->method, "у клона не совпадает `method` ");
        $this->assertEquals($request->headers, $requestClone->headers, "у клона не совпадает `headers` ");
        $this->assertEquals($request->contentType, $requestClone->contentType, "у клона не совпадает `contentType` ");

        $this->assertEquals($request->data, $requestClone->data, "у клона не совпадает `data` ");

        $this->assertEquals($request->curlOptions, $requestClone->curlOptions, "у клона не совпадает `curlOptions` ");
        $this->assertEquals($request->curlInfo, $requestClone->curlInfo, "у клона не совпадает `curlInfo` ");

        $request->setupStatusComplete();

        $this->assertEquals(RequestInterface::STATUS_PREPARE, $requestClone->status_id);
    }
}