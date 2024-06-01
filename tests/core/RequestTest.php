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

use andy87\knock_knock\core\{Handler, Request};
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\lib\{ContentType, Method};
use andy87\knock_knock\tests\helpers\UnitTestCore;
use Exception;

/**
 * Class RequestTest
 *
 * Тесты для методов класса Request
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/RequestTest.php --testdox
 *
 * @tag #test #Request
 */
class RequestTest extends UnitTestCore
{
    /** @var Request $Request */
    private Request $Request;



    /**
     * Установки
     *
     * @return void
     *
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Request = $this->getRequest(self::ENDPOINT, self::PARAMS);
    }

    /**
     * Проверка создания объекта класса `Request`
     *      Тест ожидает, что объект будет создан
     *
     * Source: @see Request::__construct()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testConstructor
     *
     * @tag #test #Request #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Request::class, $this->Request );
    }

    /**
     * Проверка доступа к ReadOnly свойствам объекта.
     *      Тест ожидает, что свойства будут доступны для чтения.
     *
     * Source: @see Request::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testMagicGet
     *
     * @tag #test #Request #magic #get
     */
    public function testMagicGet(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEquals( RequestInterface::STATUS_PREPARE, $Request->status_id );
        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PREPARE],
            $Request->statusLabel
        );

        $this->assertEqualsRequestParams( $Request );

        $this->assertEquals( self::PARAMS, $Request->params );
    }

    /**
     * Вспомогательный метод для проверки параметров запроса
     *
     * @param Request $Request
     *
     * @return void
     *
     * @tag #test #Request #helper #requestParams
     */
    private function assertEqualsRequestParams( Request $Request ): void
    {
        $this->assertEquals( self::PROTOCOL, $Request->protocol );
        $this->assertEquals( self::HOST, $Request->host );
        $this->assertEquals( self::ENDPOINT, $Request->endpoint );

        $this->assertEquals( self::METHOD, $Request->method );
        $this->assertEquals( self::HEADERS, $Request->headers );
        $this->assertEquals( self::CONTENT_TYPE, $Request->contentType );

        $this->assertEquals( self::DATA, $Request->data );

        $this->assertEquals( self::CURL_OPTIONS, $Request->curlOptions );
        $this->assertEquals( self::CURL_INFO, $Request->curlInfo );
    }


    /**
     * Проверка формирования URL для запроса методом GET.
     *      Тест ожидает, что установленный URL будет доступен в свойстве `url`
     *      с добавлением HttpBuildQuery данных в строку запроса.
     *
     * Source: @see Request::constructUrl()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testConstructUrlOnGet
     *
     * @tag #test #Request #constructUrl #get
     */
    public function testConstructUrlOnGet(): void
    {
        $Request = $this->Request->setMethod(Method::GET );
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEquals( self::PROTOCOL, $Request->protocol );
        $this->assertEquals( self::HOST, $Request->host );
        $this->assertEquals( self::ENDPOINT, $Request->endpoint );

        $url = self::PROTOCOL . '://' . self::HOST . self::ENDPOINT
            . '?' . http_build_query($Request->data);

        $this->assertEquals( $Request->url, $url );
    }

    /**
     * Проверка формирования URL для запроса методом POST.
     *      Тест ожидает, что установленный URL будет доступен в свойстве `url`
     *      без добавления данных в строку запроса.
     *
     * Source: @see Request::constructUrl()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testConstructUrlOnPost
     *
     * @tag #test #Request #constructUrl #post
     */
    public function testConstructUrlOnPost(): void
    {
        $Request = (new Handler(self::HOST))
            ->constructRequest(
                Method::POST,
                self::ENDPOINT
            );

        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEquals( self::PROTOCOL, $Request->protocol );
        $this->assertEquals( self::HOST, $Request->host );
        $this->assertEquals( self::ENDPOINT, $Request->endpoint );

        $url = self::PROTOCOL . '://' . self::HOST . self::ENDPOINT;


        $this->assertEquals( $Request->url, $url );
    }

    /**
     * Проверка подготовки `endpoint` для запроса методом GET.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *      с добавлением HttpBuildQuery данных в строку запроса.
     *
     * Source: @see Request::prepareEndpoint()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testPrepareEndpointOnGet
     *
     * @tag #test #Request #prepare #endpoint #get
     */
    public function testPrepareEndpointOnGet(): void
    {
        $Request = $this->Request->setMethod(Method::GET );
        $this->assertInstanceOf(Request::class, $Request );


        $endpoint = 'newEndpoint';
        $Request->setEndpoint($endpoint);
        $Request->setData(self::DATA);

        $Request->prepareEndpoint();

        $endpoint = 'newEndpoint?' . http_build_query(self::DATA);

        $this->assertEquals( $endpoint, $Request->endpoint );
    }

    /**
     * Проверка подготовки `endpoint` для запроса методом POST.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *      без добавления данных в строку запроса.
     *
     * Source: @see Request::prepareEndpoint()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testPrepareEndpointOnPost
     *
     * @tag #test #Request #prepare #endpoint #post
     */
    public function testPrepareEndpointOnPost(): void
    {
        $Request = $this->Request->setMethod(Method::POST );
        $this->assertInstanceOf(Request::class, $Request );

        $endpoint = 'newEndpoint';
        $Request->setEndpoint($endpoint);
        $Request->setData(self::DATA);

        $Request->prepareEndpoint();

        $this->assertEquals( $endpoint, $Request->endpoint );
    }

    /**
     * Проверка установки протокола для запроса.
     *      Тест ожидает, что установленный протокол будет доступен в свойстве `protocol`
     *
     * Source: @see Request::setProtocol()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetProtocol
     *
     * @tag #test #Request #set #protocol
     */
    public function testSetProtocol(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $protocol = 'wss';
        $Request->setProtocol($protocol);
        $this->assertEquals( $protocol, $Request->protocol );
    }

    /**
     * Проверка установки `host` для запроса.
     *      Тест ожидает, что установленный `host` будет доступен в свойстве `host`
     *
     * Source: @see Request::setHost()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetHost
     *
     * @tag #test #Request #set #host
     */
    public function testSetHost(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $host = 'newHost';
        $Request->setHost($host);
        $this->assertEquals( $host, $Request->host );
    }

    /**
     * Проверка установки `endpoint` для запроса.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *
     * Source: @see Request::setEndpoint()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetEndpoint
     *
     * @tag #test #Request #set #endpoint
     */
    public function testSetEndpoint(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $endpoint = 'newEndpoint';
        $Request->setEndpoint($endpoint);
        $this->assertEquals( $endpoint, $Request->endpoint );
    }

    /**
     * Проверка установки метода запроса.
     *      Тест ожидает, что установленный метод будет доступен в свойстве `method`
     *
     * Source: @see Request::setMethod()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetMethod
     *
     * @tag #test #Request #set #method
     */
    public function testSetMethod(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $Request->setMethod(Method::PATCH);
        $this->assertEquals( Method::PATCH, $Request->method );
    }

    /**
     * Проверка установки одного заголовка к запросу.
     *      Тест ожидает, что установленный заголовок будет доступен в свойстве `headers`
     *
     * Source: @see Request::setHeader()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetHeader
     *
     * @tag #test #Request #set #headers
     */
    public function testSetHeader(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $headerKey = 'newHeaderKey';
        $headerValue = 'newHeaderValue';

        $Request->setHeader($headerKey, $headerValue);

        $this->assertEquals( $headerValue, $Request->headers[$headerKey] );
    }

    /**
     * Проверка добавления заголовков к запросу.
     *      Тест ожидает, что добавленные заголовки будут доступны в свойстве `headers`
     *
     * Source: @see Request::addHeaders()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testAddHeaders
     *
     * @tag #test #Request #headers #add
     */
    public function testAddHeaders(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $headers = [
            'a' => 'c',
            'b' => 'd',
        ];
        $Request->addHeaders($headers);

        $this->assertEquals( $headers['a'], $Request->headers['a'] );
        $this->assertEquals( $headers['b'], $Request->headers['b'] );
    }

    /**
     * Проверка установки `contentType` для запроса.
     *      Тест ожидает, что установленный тип контента будет доступен в свойстве `contentType`
     *
     * Source: @see Request::setContentType()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetContentType
     *
     * @tag #test #Request #set #contentType
     */
    public function testSetContentType(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $Request->setContentType(ContentType::MULTIPART);

        $this->assertEquals( ContentType::MULTIPART, $Request->contentType );
    }

    /**
     * Проверка установки данных для запроса.
     *      Тест ожидает, что установленные данные будут доступны в свойстве `data`
     *
     * Source: @see Request::setData()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetData
     *
     * @tag #test #Request #set #data
     */
    public function testSetData(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $data = ['newDataKey' => 'newDataValue'];

        $Request->setData($data);

        $this->assertEquals( $data, $Request->data );
    }

    /**
     * Проверка установки опций для запроса.
     *      Тест ожидает, что установленные опции будут доступны в свойстве `curlOptions`
     *
     * Source: @see Request::setCurlOptions()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetCurlOptions
     *
     * @tag #test #Request #set #curlOptions
     */
    public function testSetCurlOptions(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $curlOptions = [CURLOPT_TIMEOUT => 60];

        $Request->setCurlOptions($curlOptions);

        $this->assertEquals( $curlOptions, $Request->curlOptions );
    }

    /**
     * Проверка добавления опций к запросу.
     *      Тест ожидает, что добавленные опции будут доступны в свойстве `curlOptions`
     *
     * Source: @see Request::addCurlOptions()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testAddCurlOptions
     *
     * @tag #test #Request #add #curlOptions
     */
    public function testAddCurlOptions(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $curlOptions = [
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30
        ];

        $Request->addCurlOptions($curlOptions);

        $this->assertEquals( $curlOptions, $Request->curlOptions );
    }

    /**
     * Проверка установки информации о запросе.
     *      Тест ожидает, что установленные значения будут доступны в свойстве `curlInfo`
     *
     * Source: @see Request::setCurlInfo()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetCurlInfo
     *
     * @tag #test #Request #set #curlInfo
     */
    public function testSetCurlInfo(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $curlInfo = [
            CURLINFO_CONTENT_TYPE,
            CURLINFO_HEADER_SIZE,
            CURLINFO_TOTAL_TIME
        ];

        $Request->setCurlInfo($curlInfo);

        $this->assertEquals( $curlInfo, $Request->curlInfo );
    }

    /**
     * Проверка добавления ошибки в массив ошибок запроса.
     *      Тест ожидает, что добавленная ошибка будет доступна по ключу.
     *
     * Source: @see Request::addError()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testAddError
     *
     * @tag #test #Request #add #error
     */
    public function testAddError(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $errorKey = 'errorKey';
        $errorText = 'errorText';

        $Request->addError($errorText, $errorKey);

        $this->assertEquals( $errorText, $Request->errors[$errorKey] );
    }

    /**
     * Проверка назначения запросу статуса - "в обработке".
     *      Тест ожидает актуальные значения в свойствах `status_id` и `statusLabel`
     *
     * Source: @see Request::setupStatusProcessing()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetupStatusProcessing
     *
     * @tag #test #Request #status #processing
     */
    public function testSetupStatusProcessing(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $Request->setupStatusProcessing();

        $this->assertEquals( RequestInterface::STATUS_PROCESSING, $Request->status_id );
        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PROCESSING],
            $Request->statusLabel
        );
    }

    /**
     * Проверка назначения запросу статуса - "завершён".
     *      Тест ожидает актуальные значения в свойствах `status_id` и `statusLabel`
     *
     * Source: @see Request::setupStatusComplete()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetupStatusComplete
     *
     * @tag #test #Request #status #complete
     */
    public function testSetupStatusComplete(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $Request->setupStatusComplete();

        $this->assertEquals( RequestInterface::STATUS_COMPLETE, $Request->status_id );
        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_COMPLETE],
            $Request->statusLabel
        );
    }

    /**
     * Проверка, что запрос завершён.
     *      Тест ожидает `false` на проверку значения статуса = `STATUS_COMPLETE` при новом, созданном объекте
     *      и `true` после изменения статуса на `STATUS_COMPLETE`
     *
     * Source: @see Request::statusIsComplete()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testStatusIsComplete
     *
     * @tag #test #Request #status #complete
     */
    public function testStatusIsComplete(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertFalse( $Request->statusIsComplete() );

        $Request->setupStatusComplete();

        $this->assertTrue( $Request->statusIsComplete() );
    }

    /**
     * Проверка установленного статуса запроса - "подготовка".
     *      Тест ожидает у созданного объекта `Request` статус `STATUS_PREPARE`,
     *      а после изменения статуса на `STATUS_COMPLETE` ожидает `false`
     *
     * Source: @see Request::statusIsPrepare()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testStatusIsPrepare
     *
     * @tag #test #Request #status #prepare
     */
    public function testStatusIsPrepare(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertTrue( $Request->statusIsPrepare() );

        $Request->setupStatusComplete();

        $this->assertFalse( $Request->statusIsPrepare() );
    }

    /**
     * Проверка данных указывающих на ОТКЛЮЧЕНИЕ проверки SSL
     *      Тест ожидает определённые значения
     *      в свойствах `curlOptions[CURLOPT_SSL_VERIFYPEER]` и `curlOptions[CURLOPT_SSL_VERIFYHOST]`
     *
     * Source: @see Request::disableSSL()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testDisableSSL
     *
     * @tag #test #Request #ssl #disable
     */
    public function testDisableSSL(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertFalse(isset($Request->curlOptions[CURLOPT_SSL_VERIFYPEER]));
        $this->assertFalse(isset($Request->curlOptions[CURLOPT_SSL_VERIFYHOST]));

        $Request->disableSSL();

        $this->assertFalse($Request->curlOptions[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals( 0, $Request->curlOptions[CURLOPT_SSL_VERIFYHOST] );
    }

    /**
     * Проверка данных указывающих на ВКЛЮЧЕНИЕ проверки SSL
     *      Тест ожидает определённые значения
     *      в свойствах `curlOptions[CURLOPT_SSL_VERIFYPEER]` и `curlOptions[CURLOPT_SSL_VERIFYHOST]`
     *
     * Source: @see Request::enableSSL()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testEnableSSL
     *
     * @tag #test #Request #ssl #enable
     */
    public function testEnableSSL(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertFalse(isset($Request->curlOptions[CURLOPT_SSL_VERIFYPEER]));
        $this->assertFalse(isset($Request->curlOptions[CURLOPT_SSL_VERIFYHOST]));

        $Request->enableSSL();

        $this->assertTrue($Request->curlOptions[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals( 2, $Request->curlOptions[CURLOPT_SSL_VERIFYHOST] );
    }

    /**
     * Проверка, невозможности назначения свойств запросу который уже выполнен.
     *      Тест ожидает `Exception` потому что запрос уже завершен(статус `STATUS_COMPLETE`)
     *      и нельзя изменить параметры запроса.
     *
     * Source: @see Request::limiterIsComplete()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testLimiterIsComplete
     *
     * @tag #test #Request #limiter #status #complete
     */
    public function testLimiterIsComplete(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $Request->setupStatusComplete();

        $this->expectException(Exception::class);
        $Request->setProtocol('newProtocol');
        $Request->setHost('newHost');
        $Request->setEndpoint('newEndpoint');
        $Request->setMethod(Method::PATCH);
        $Request->setContentType(ContentType::MULTIPART);
        $Request->setHeader('newHeaderKey', 'newHeaderValue');
        $Request->setData(['newDataKey' => 'newDataValue']);
        $Request->setCurlOptions([CURLOPT_TIMEOUT => 60]);
        $Request->setCurlInfo([CURLINFO_CONTENT_TYPE]);
    }

    /**
     * Проверка подготовки `domain` для запроса.
     *      Тест ожидает, что собранный `domain` будет доступен в свойстве `domain`
     *
     * Source: @see Request::prepareHost()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testPrepareHost
     *
     * @tag #test #Request #prepare #host
     */
    public function testPrepareHost(): void
    {
        $protocol = 'http';
        $host = 'first.host';

        $Handler = new Handler("$protocol://$host");
        $this->assertInstanceOf(Handler::class, $Handler );

        $this->assertEquals( $protocol, $Handler->commonRequest->protocol );
        $this->assertEquals( $host, $Handler->commonRequest->host );

        $protocol = 'wss';
        $host = 'second.host';
        $Handler->commonRequest->setHost("$protocol://$host");

        $this->assertEquals( $protocol, $Handler->commonRequest->protocol );
        $this->assertEquals( $host, $Handler->commonRequest->host );

        $protocol = 'https';
        $host = 'next.host';
        $endpoint = 'endpoint';
        $Handler->commonRequest->setProtocol($protocol);
        $Handler->commonRequest->setHost($host);
        $Handler->commonRequest->setEndpoint($endpoint);
        $Handler->commonRequest->constructUrl();

        $this->assertEquals( $protocol, $Handler->commonRequest->protocol );
        $this->assertEquals( $host, $Handler->commonRequest->host );
    }

    /**
     * Проверка установки параметров запроса из массива.
     *      Тест ожидает, что установленные параметры будут доступны в свойствах объекта
     *
     * Source: @see Request::setupParamsFromArray()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetupParamsFromArray
     *
     * @tag #test #Request #setup #params
     */
    public function testSetupParamsFromArray(): void
    {
        $Request = new Request(self::HOST, self::PARAMS );

        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEqualsRequestParams( $Request );
    }

    /**
     * Проверка установки параметров запроса в статусе "подготовка".
     *      Тест ожидает, что можно установить параметры запроса в статусе "подготовка"
     *      и они будут доступны в свойствах объекта.
     *      Тест ожидает, что нельзя установить параметры запроса в статусе "завершён"
     *      и будет выброшено исключение.
     *
     * Source: @see Request::setParamsOnStatusPrepare()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testSetParamsOnStatusPrepare
     *
     * @tag #test #Request #set #prepare #params
     */
    public function testSetParamsOnStatusPrepare(): void
    {
        $Request = $this->getRequest(self::ENDPOINT, []);
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEquals( RequestInterface::STATUS_PREPARE, $Request->status_id );

        $this->assertInstanceOf(Request::class, $Request->setProtocol(self::PROTOCOL));
        $this->assertInstanceOf(Request::class, $Request->setHost(self::HOST));
        $this->assertInstanceOf(Request::class, $Request->setEndpoint(self::ENDPOINT));
        $this->assertInstanceOf(Request::class, $Request->setMethod(self::METHOD));
        $this->assertInstanceOf(Request::class, $Request->setContentType(self::CONTENT_TYPE));
        $this->assertInstanceOf(Request::class, $Request->setData(self::DATA));
        $this->assertInstanceOf(Request::class, $Request->setHeader('newHeaderKey', 'newHeaderValue'));
        $this->assertInstanceOf(Request::class, $Request->addHeaders(self::HEADERS));
        $this->assertInstanceOf(Request::class, $Request->setCurlOptions(self::CURL_OPTIONS));
        $this->assertInstanceOf(Request::class, $Request->setCurlInfo(self::CURL_INFO));

        $Request->setupStatusComplete();

        $this->assertEquals( RequestInterface::STATUS_COMPLETE, $Request->status_id );

        $this->expectException(Exception::class);
        $Request->setProtocol(self::PROTOCOL);
    }

    /**
     * Проверка получения текстового статуса запроса.
     *      Тест ожидает, что текстовый статус запроса будет актуальным
     *
     * Source: @see Request::getStatusLabel()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testGetStatusLabel
     *
     * @tag #test #Request #status #label
     */
    public function testGetStatusLabel(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PREPARE],
            $Request->statusLabel
        );

        $Request->setupStatusProcessing();

        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_PROCESSING],
            $Request->statusLabel
        );

        $Request->setupStatusComplete();

        $this->assertEquals(
            Request::LABELS_STATUS[RequestInterface::STATUS_COMPLETE],
            $Request->statusLabel
        );
    }

    /**
     * Проверка получения параметров запроса.
     *      Тест ожидает, что параметры запроса будут актуальными
     *
     * Source: @see Request::getParams()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testGetParams
     *
     * @tag #test #Request #get #params
     */
    public function testGetParams(): void
    {
        $Request = new Request(self::HOST, self::PARAMS);
        $this->assertInstanceOf(Request::class, $Request );

        $originalJson = json_encode(self::PARAMS);
        $resultJson = json_encode($Request->params);

        $this->assertEquals( $originalJson, $resultJson );
    }

    /**
     * Проверка получения ошибок запроса.
     *      Тест ожидает, ожидает получить из запроса все ошибки отправленные в него
     *
     * Source: @see Request::getErrors()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testGetErrors
     *
     * @tag #test #Request #get #errors
     */
    public function testGetErrors(): void
    {
        $Request = $this->Request;
        $this->assertInstanceOf(Request::class, $Request );

        $this->assertEquals( [], $Request->errors );

        $errorKey = 'errorKey';
        $errorText = 'errorText';

        $Request->addError( $errorText, $errorKey );

        $this->assertEquals( [$errorKey => $errorText], $Request->errors );

        $Request->addError( 'next Error' );

        $this->assertCount( 2, $Request->errors );
    }

    /**
     * Проверка клонирования объекта запроса.
     *      Тест ожидает, что клонированный объект будет идентичен исходному, за исключением статуса запроса.
     *
     * Source: @see Request::clone()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/RequestTest.php --testdox --filter testClone
     *
     * @tag #test #Request #clone
     */
    public function testClone(): void
    {
        $Request = new Request(self::HOST, self::PARAMS);
        $this->assertInstanceOf(Request::class, $Request );

        $RequestClone = $Request->clone();

        $this->assertEquals( $Request->protocol, $RequestClone->protocol, "у клона не совпадает `protocol` " );
        $this->assertEquals( $Request->host, $RequestClone->host, "у клона не совпадает `host` " );
        $this->assertEquals( $Request->endpoint, $RequestClone->endpoint, "у клона не совпадает `endpoint` " );

        $this->assertEquals( $Request->method, $RequestClone->method, "у клона не совпадает `method` " );
        $this->assertEquals( $Request->headers, $RequestClone->headers,  "у клона не совпадает `headers` " );
        $this->assertEquals( $Request->contentType, $RequestClone->contentType, "у клона не совпадает `contentType` ");

        $this->assertEquals( $Request->data, $RequestClone->data, "у клона не совпадает `data` ");

        $this->assertEquals( $Request->curlOptions, $RequestClone->curlOptions, "у клона не совпадает `curlOptions` ");
        $this->assertEquals( $Request->curlInfo, $RequestClone->curlInfo, "у клона не совпадает `curlInfo` ");

        $Request->setupStatusComplete();

        $this->assertEquals( RequestInterface::STATUS_PREPARE, $RequestClone->status_id );
    }
}