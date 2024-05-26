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
use tests\core\UnitTestCore;
use andy87\knock_knock\interfaces\KnockRequestInterface;
use andy87\knock_knock\core\{ KnockKnock, KnockRequest };
use andy87\knock_knock\lib\{ LibKnockMethod, LibKnockContentType };

/**
 * Class KnockRequestTest
 *
 * Тесты для методов класса KnockRequest
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/KnockRequestTest.php --testdox
 *
 * @tag #test #knockRequest
 */
class KnockRequestTest extends UnitTestCore
{
    /** @var KnockRequest $knockRequest */
    private KnockRequest $knockRequest;



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

        $this->knockRequest = $this->getKnockRequest(self::ENDPOINT, self::PARAMS);
    }

    /**
     * Проверка создания объекта класса `KnockRequest`
     *      Тест ожидает, что объект будет создан
     *
     * Source: @see KnockRequest::__construct()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testConstructor
     *
     * @tag #test #knockRequest #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(KnockRequest::class, $this->knockRequest );
    }

    /**
     * Проверка доступа к ReadOnly свойствам объекта.
     *      Тест ожидает, что свойства будут доступны для чтения.
     *
     * Source: @see KnockRequest::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testMagicGet
     *
     * @tag #test #knockRequest #magic #get
     */
    public function testMagicGet(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEquals( KnockRequestInterface::STATUS_PREPARE, $knockRequest->status_id );
        $this->assertEquals(
            KnockRequest::LABELS_STATUS[KnockRequestInterface::STATUS_PREPARE],
            $knockRequest->statusLabel
        );

        $this->assertEqualsRequestParams( $knockRequest );

        $this->assertEquals( self::PARAMS, $knockRequest->params );
    }

    /**
     * Вспомогательный метод для проверки параметров запроса
     *
     * @param KnockRequest $knockRequest
     *
     * @return void
     *
     * @tag #test #knockRequest #helper #requestParams
     */
    private function assertEqualsRequestParams( KnockRequest $knockRequest ): void
    {
        $this->assertEquals( self::PROTOCOL, $knockRequest->protocol );
        $this->assertEquals( self::HOST, $knockRequest->host );
        $this->assertEquals( self::ENDPOINT, $knockRequest->endpoint );

        $this->assertEquals( self::METHOD, $knockRequest->method );
        $this->assertEquals( self::HEADERS, $knockRequest->headers );
        $this->assertEquals( self::CONTENT_TYPE, $knockRequest->contentType );

        $this->assertEquals( self::DATA, $knockRequest->data );

        $this->assertEquals( self::CURL_OPTIONS, $knockRequest->curlOptions );
        $this->assertEquals( self::CURL_INFO, $knockRequest->curlInfo );
    }


    /**
     * Проверка формирования URL для запроса методом GET.
     *      Тест ожидает, что установленный URL будет доступен в свойстве `url`
     *      с добавлением HttpBuildQuery данных в строку запроса.
     *
     * Source: @see KnockRequest::constructUrl()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testConstructUrlOnGet
     *
     * @tag #test #knockRequest #constructUrl #get
     */
    public function testConstructUrlOnGet(): void
    {
        $knockRequest = $this->knockRequest->setMethod(LibKnockMethod::GET );
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEquals( self::PROTOCOL, $knockRequest->protocol );
        $this->assertEquals( self::HOST, $knockRequest->host );
        $this->assertEquals( self::ENDPOINT, $knockRequest->endpoint );

        $url = self::PROTOCOL . '://' . self::HOST . self::ENDPOINT
            . '?' . http_build_query($knockRequest->data);

        $this->assertEquals( $knockRequest->url, $url );
    }

    /**
     * Проверка формирования URL для запроса методом POST.
     *      Тест ожидает, что установленный URL будет доступен в свойстве `url`
     *      без добавления данных в строку запроса.
     *
     * Source: @see KnockRequest::constructUrl()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testConstructUrlOnPost
     *
     * @tag #test #knockRequest #constructUrl #post
     */
    public function testConstructUrlOnPost(): void
    {
        $knockRequest = (new KnockKnock(self::HOST))
            ->constructRequest(
                LibKnockMethod::POST,
                self::ENDPOINT
            );

        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEquals( self::PROTOCOL, $knockRequest->protocol );
        $this->assertEquals( self::HOST, $knockRequest->host );
        $this->assertEquals( self::ENDPOINT, $knockRequest->endpoint );

        $url = self::PROTOCOL . '://' . self::HOST . self::ENDPOINT;


        $this->assertEquals( $knockRequest->url, $url );
    }

    /**
     * Проверка подготовки `endpoint` для запроса методом GET.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *      с добавлением HttpBuildQuery данных в строку запроса.
     *
     * Source: @see KnockRequest::prepareEndpoint()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testPrepareEndpointOnGet
     *
     * @tag #test #knockRequest #prepare #endpoint #get
     */
    public function testPrepareEndpointOnGet(): void
    {
        $knockRequest = $this->knockRequest->setMethod(LibKnockMethod::GET );
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );


        $endpoint = 'newEndpoint';
        $knockRequest->setEndpoint($endpoint);
        $knockRequest->setData(self::DATA);

        $knockRequest->prepareEndpoint();

        $endpoint = 'newEndpoint?' . http_build_query(self::DATA);

        $this->assertEquals( $endpoint, $knockRequest->endpoint );
    }

    /**
     * Проверка подготовки `endpoint` для запроса методом POST.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *      без добавления данных в строку запроса.
     *
     * Source: @see KnockRequest::prepareEndpoint()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testPrepareEndpointOnPost
     *
     * @tag #test #knockRequest #prepare #endpoint #post
     */
    public function testPrepareEndpointOnPost(): void
    {
        $knockRequest = $this->knockRequest->setMethod(LibKnockMethod::POST );
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $endpoint = 'newEndpoint';
        $knockRequest->setEndpoint($endpoint);
        $knockRequest->setData(self::DATA);

        $knockRequest->prepareEndpoint();

        $this->assertEquals( $endpoint, $knockRequest->endpoint );
    }

    /**
     * Проверка установки протокола для запроса.
     *      Тест ожидает, что установленный протокол будет доступен в свойстве `protocol`
     *
     * Source: @see KnockRequest::setProtocol()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetProtocol
     *
     * @tag #test #knockRequest #set #protocol
     */
    public function testSetProtocol(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $protocol = 'wss';
        $knockRequest->setProtocol($protocol);
        $this->assertEquals( $protocol, $knockRequest->protocol );
    }

    /**
     * Проверка установки `host` для запроса.
     *      Тест ожидает, что установленный `host` будет доступен в свойстве `host`
     *
     * Source: @see KnockRequest::setHost()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetHost
     *
     * @tag #test #knockRequest #set #host
     */
    public function testSetHost(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $host = 'newHost';
        $knockRequest->setHost($host);
        $this->assertEquals( $host, $knockRequest->host );
    }

    /**
     * Проверка установки `endpoint` для запроса.
     *      Тест ожидает, что установленный `endpoint` будет доступен в свойстве `endpoint`
     *
     * Source: @see KnockRequest::setEndpoint()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetEndpoint
     *
     * @tag #test #knockRequest #set #endpoint
     */
    public function testSetEndpoint(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $endpoint = 'newEndpoint';
        $knockRequest->setEndpoint($endpoint);
        $this->assertEquals( $endpoint, $knockRequest->endpoint );
    }

    /**
     * Проверка установки метода запроса.
     *      Тест ожидает, что установленный метод будет доступен в свойстве `method`
     *
     * Source: @see KnockRequest::setMethod()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetMethod
     *
     * @tag #test #knockRequest #set #method
     */
    public function testSetMethod(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockRequest->setMethod(LibKnockMethod::PATCH);
        $this->assertEquals( LibKnockMethod::PATCH, $knockRequest->method );
    }

    /**
     * Проверка установки одного заголовка к запросу.
     *      Тест ожидает, что установленный заголовок будет доступен в свойстве `headers`
     *
     * Source: @see KnockRequest::setHeader()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetHeader
     *
     * @tag #test #knockRequest #set #headers
     */
    public function testSetHeader(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $headerKey = 'newHeaderKey';
        $headerValue = 'newHeaderValue';

        $knockRequest->setHeader($headerKey, $headerValue);

        $this->assertEquals( $headerValue, $knockRequest->headers[$headerKey] );
    }

    /**
     * Проверка добавления заголовков к запросу.
     *      Тест ожидает, что добавленные заголовки будут доступны в свойстве `headers`
     *
     * Source: @see KnockRequest::addHeaders()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testAddHeaders
     *
     * @tag #test #knockRequest #headers #add
     */
    public function testAddHeaders(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $headers = [
            'a' => 'c',
            'b' => 'd',
        ];
        $knockRequest->addHeaders($headers);

        $this->assertEquals( $headers['a'], $knockRequest->headers['a'] );
        $this->assertEquals( $headers['b'], $knockRequest->headers['b'] );
    }

    /**
     * Проверка установки `contentType` для запроса.
     *      Тест ожидает, что установленный тип контента будет доступен в свойстве `contentType`
     *
     * Source: @see KnockRequest::setContentType()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetContentType
     *
     * @tag #test #knockRequest #set #contentType
     */
    public function testSetContentType(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockRequest->setContentType(LibKnockContentType::MULTIPART);

        $this->assertEquals( LibKnockContentType::MULTIPART, $knockRequest->contentType );
    }

    /**
     * Проверка установки данных для запроса.
     *      Тест ожидает, что установленные данные будут доступны в свойстве `data`
     *
     * Source: @see KnockRequest::setData()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetData
     *
     * @tag #test #knockRequest #set #data
     */
    public function testSetData(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $data = ['newDataKey' => 'newDataValue'];

        $knockRequest->setData($data);

        $this->assertEquals( $data, $knockRequest->data );
    }

    /**
     * Проверка установки опций для запроса.
     *      Тест ожидает, что установленные опции будут доступны в свойстве `curlOptions`
     *
     * Source: @see KnockRequest::setCurlOptions()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetCurlOptions
     *
     * @tag #test #knockRequest #set #curlOptions
     */
    public function testSetCurlOptions(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $curlOptions = [CURLOPT_TIMEOUT => 60];

        $knockRequest->setCurlOptions($curlOptions);

        $this->assertEquals( $curlOptions, $knockRequest->curlOptions );
    }

    /**
     * Проверка добавления опций к запросу.
     *      Тест ожидает, что добавленные опции будут доступны в свойстве `curlOptions`
     *
     * Source: @see KnockRequest::addCurlOptions()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testAddCurlOptions
     *
     * @tag #test #knockRequest #add #curlOptions
     */
    public function testAddCurlOptions(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $curlOptions = [
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30
        ];

        $knockRequest->addCurlOptions($curlOptions);

        $this->assertEquals( $curlOptions, $knockRequest->curlOptions );
    }

    /**
     * Проверка установки информации о запросе.
     *      Тест ожидает, что установленные значения будут доступны в свойстве `curlInfo`
     *
     * Source: @see KnockRequest::setCurlInfo()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetCurlInfo
     *
     * @tag #test #knockRequest #set #curlInfo
     */
    public function testSetCurlInfo(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $curlInfo = [
            CURLINFO_CONTENT_TYPE,
            CURLINFO_HEADER_SIZE,
            CURLINFO_TOTAL_TIME
        ];

        $knockRequest->setCurlInfo($curlInfo);

        $this->assertEquals( $curlInfo, $knockRequest->curlInfo );
    }

    /**
     * Проверка добавления ошибки в массив ошибок запроса.
     *      Тест ожидает, что добавленная ошибка будет доступна по ключу.
     *
     * Source: @see KnockRequest::addError()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testAddError
     *
     * @tag #test #knockRequest #add #error
     */
    public function testAddError(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $errorKey = 'errorKey';
        $errorText = 'errorText';

        $knockRequest->addError($errorText, $errorKey);

        $this->assertEquals( $errorText, $knockRequest->errors[$errorKey] );
    }

    /**
     * Проверка назначения запросу статуса - "в обработке".
     *      Тест ожидает актуальные значения в свойствах `status_id` и `statusLabel`
     *
     * Source: @see KnockRequest::setupStatusProcessing()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetupStatusProcessing
     *
     * @tag #test #knockRequest #status #processing
     */
    public function testSetupStatusProcessing(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockRequest->setupStatusProcessing();

        $this->assertEquals( KnockRequestInterface::STATUS_PROCESSING, $knockRequest->status_id );
        $this->assertEquals(
            KnockRequest::LABELS_STATUS[KnockRequestInterface::STATUS_PROCESSING],
            $knockRequest->statusLabel
        );
    }

    /**
     * Проверка назначения запросу статуса - "завершён".
     *      Тест ожидает актуальные значения в свойствах `status_id` и `statusLabel`
     *
     * Source: @see KnockRequest::setupStatusComplete()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetupStatusComplete
     *
     * @tag #test #knockRequest #status #complete
     */
    public function testSetupStatusComplete(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockRequest->setupStatusComplete();

        $this->assertEquals( KnockRequestInterface::STATUS_COMPLETE, $knockRequest->status_id );
        $this->assertEquals(
            KnockRequest::LABELS_STATUS[KnockRequestInterface::STATUS_COMPLETE],
            $knockRequest->statusLabel
        );
    }

    /**
     * Проверка, что запрос завершён.
     *      Тест ожидает `false` на проверку значения статуса = `STATUS_COMPLETE` при новом, созданном объекте
     *      и `true` после изменения статуса на `STATUS_COMPLETE`
     *
     * Source: @see KnockRequest::statusIsComplete()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testStatusIsComplete
     *
     * @tag #test #knockRequest #status #complete
     */
    public function testStatusIsComplete(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertFalse( $knockRequest->statusIsComplete() );

        $knockRequest->setupStatusComplete();

        $this->assertTrue( $knockRequest->statusIsComplete() );
    }

    /**
     * Проверка установленного статуса запроса - "подготовка".
     *      Тест ожидает у созданного объекта `KnockRequest` статус `STATUS_PREPARE`,
     *      а после изменения статуса на `STATUS_COMPLETE` ожидает `false`
     *
     * Source: @see KnockRequest::statusIsPrepare()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testStatusIsPrepare
     *
     * @tag #test #knockRequest #status #prepare
     */
    public function testStatusIsPrepare(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertTrue( $knockRequest->statusIsPrepare() );

        $knockRequest->setupStatusComplete();

        $this->assertFalse( $knockRequest->statusIsPrepare() );
    }

    /**
     * Проверка данных указывающих на ОТКЛЮЧЕНИЕ проверки SSL
     *      Тест ожидает определённые значения
     *      в свойствах `curlOptions[CURLOPT_SSL_VERIFYPEER]` и `curlOptions[CURLOPT_SSL_VERIFYHOST]`
     *
     * Source: @see KnockRequest::disableSSL()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testDisableSSL
     *
     * @tag #test #knockRequest #ssl #disable
     */
    public function testDisableSSL(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertFalse(isset($knockRequest->curlOptions[CURLOPT_SSL_VERIFYPEER]));
        $this->assertFalse(isset($knockRequest->curlOptions[CURLOPT_SSL_VERIFYHOST]));

        $knockRequest->disableSSL();

        $this->assertFalse($knockRequest->curlOptions[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals( 0, $knockRequest->curlOptions[CURLOPT_SSL_VERIFYHOST] );
    }

    /**
     * Проверка данных указывающих на ВКЛЮЧЕНИЕ проверки SSL
     *      Тест ожидает определённые значения
     *      в свойствах `curlOptions[CURLOPT_SSL_VERIFYPEER]` и `curlOptions[CURLOPT_SSL_VERIFYHOST]`
     *
     * Source: @see KnockRequest::enableSSL()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testEnableSSL
     *
     * @tag #test #knockRequest #ssl #enable
     */
    public function testEnableSSL(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertFalse(isset($knockRequest->curlOptions[CURLOPT_SSL_VERIFYPEER]));
        $this->assertFalse(isset($knockRequest->curlOptions[CURLOPT_SSL_VERIFYHOST]));

        $knockRequest->enableSSL();

        $this->assertTrue($knockRequest->curlOptions[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals( 2, $knockRequest->curlOptions[CURLOPT_SSL_VERIFYHOST] );
    }

    /**
     * Проверка, невозможности назначения свойств запросу который уже выполнен.
     *      Тест ожидает `Exception` потому что запрос уже завершен(статус `STATUS_COMPLETE`)
     *      и нельзя изменить параметры запроса.
     *
     * Source: @see KnockRequest::limiterIsComplete()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testLimiterIsComplete
     *
     * @tag #test #knockRequest #limiter #status #complete
     */
    public function testLimiterIsComplete(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockRequest->setupStatusComplete();

        $this->expectException(Exception::class);
        $knockRequest->setProtocol('newProtocol');
        $knockRequest->setHost('newHost');
        $knockRequest->setEndpoint('newEndpoint');
        $knockRequest->setMethod(LibKnockMethod::PATCH);
        $knockRequest->setContentType(LibKnockContentType::MULTIPART);
        $knockRequest->setHeader('newHeaderKey', 'newHeaderValue');
        $knockRequest->setData(['newDataKey' => 'newDataValue']);
        $knockRequest->setCurlOptions([CURLOPT_TIMEOUT => 60]);
        $knockRequest->setCurlInfo([CURLINFO_CONTENT_TYPE]);
    }

    /**
     * Проверка подготовки `domain` для запроса.
     *      Тест ожидает, что собранный `domain` будет доступен в свойстве `domain`
     *
     * Source: @see KnockRequest::prepareHost()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testPrepareHost
     *
     * @tag #test #knockRequest #prepare #host
     */
    public function testPrepareHost(): void
    {
        $protocol = 'http';
        $host = 'first.host';

        $knockKnock = new KnockKnock("$protocol://$host");
        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $this->assertEquals( $protocol, $knockKnock->commonKnockRequest->protocol );
        $this->assertEquals( $host, $knockKnock->commonKnockRequest->host );

        $protocol = 'wss';
        $host = 'second.host';
        $knockKnock->commonKnockRequest->setHost("$protocol://$host");

        $this->assertEquals( $protocol, $knockKnock->commonKnockRequest->protocol );
        $this->assertEquals( $host, $knockKnock->commonKnockRequest->host );

        $protocol = 'https';
        $host = 'next.host';
        $endpoint = 'endpoint';
        $knockKnock->commonKnockRequest->setProtocol($protocol);
        $knockKnock->commonKnockRequest->setHost($host);
        $knockKnock->commonKnockRequest->setEndpoint($endpoint);
        $knockKnock->commonKnockRequest->constructUrl();

        $this->assertEquals( $protocol, $knockKnock->commonKnockRequest->protocol );
        $this->assertEquals( $host, $knockKnock->commonKnockRequest->host );
    }

    /**
     * Проверка установки параметров запроса из массива.
     *      Тест ожидает, что установленные параметры будут доступны в свойствах объекта
     *
     * Source: @see KnockRequest::setupParamsFromArray()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetupParamsFromArray
     *
     * @tag #test #knockRequest #setup #params
     */
    public function testSetupParamsFromArray(): void
    {
        $knockRequest = new KnockRequest(self::HOST, self::PARAMS );

        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEqualsRequestParams( $knockRequest );
    }

    /**
     * Проверка установки параметров запроса в статусе "подготовка".
     *      Тест ожидает, что можно установить параметры запроса в статусе "подготовка"
     *      и они будут доступны в свойствах объекта.
     *      Тест ожидает, что нельзя установить параметры запроса в статусе "завершён"
     *      и будет выброшено исключение.
     *
     * Source: @see KnockRequest::setParamsOnStatusPrepare()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testSetParamsOnStatusPrepare
     *
     * @tag #test #knockRequest #set #prepare #params
     */
    public function testSetParamsOnStatusPrepare(): void
    {
        $knockRequest = $this->getKnockRequest(self::ENDPOINT, []);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEquals( KnockRequestInterface::STATUS_PREPARE, $knockRequest->status_id );

        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setProtocol(self::PROTOCOL));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setHost(self::HOST));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setEndpoint(self::ENDPOINT));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setMethod(self::METHOD));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setContentType(self::CONTENT_TYPE));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setData(self::DATA));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setHeader('newHeaderKey', 'newHeaderValue'));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->addHeaders(self::HEADERS));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setCurlOptions(self::CURL_OPTIONS));
        $this->assertInstanceOf(KnockRequest::class, $knockRequest->setCurlInfo(self::CURL_INFO));

        $knockRequest->setupStatusComplete();

        $this->assertEquals( KnockRequestInterface::STATUS_COMPLETE, $knockRequest->status_id );

        $this->expectException(Exception::class);
        $knockRequest->setProtocol(self::PROTOCOL);
    }

    /**
     * Проверка получения текстового статуса запроса.
     *      Тест ожидает, что текстовый статус запроса будет актуальным
     *
     * Source: @see KnockRequest::getStatusLabel()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testGetStatusLabel
     *
     * @tag #test #knockRequest #status #label
     */
    public function testGetStatusLabel(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEquals(
            KnockRequest::LABELS_STATUS[KnockRequestInterface::STATUS_PREPARE],
            $knockRequest->statusLabel
        );

        $knockRequest->setupStatusProcessing();

        $this->assertEquals(
            KnockRequest::LABELS_STATUS[KnockRequestInterface::STATUS_PROCESSING],
            $knockRequest->statusLabel
        );

        $knockRequest->setupStatusComplete();

        $this->assertEquals(
            KnockRequest::LABELS_STATUS[KnockRequestInterface::STATUS_COMPLETE],
            $knockRequest->statusLabel
        );
    }

    /**
     * Проверка получения параметров запроса.
     *      Тест ожидает, что параметры запроса будут актуальными
     *
     * Source: @see KnockRequest::getParams()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testGetParams
     *
     * @tag #test #knockRequest #get #params
     */
    public function testGetParams(): void
    {
        $knockRequest = new KnockRequest(self::HOST, self::PARAMS);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $originalJson = json_encode(self::PARAMS);
        $resultJson = json_encode($knockRequest->params);

        $this->assertEquals( $originalJson, $resultJson );
    }

    /**
     * Проверка получения ошибок запроса.
     *      Тест ожидает, ожидает получить из запроса все ошибки отправленные в него
     *
     * Source: @see KnockRequest::getErrors()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockRequestTest.php --filter testGetErrors
     *
     * @tag #test #knockRequest #get #errors
     */
    public function testGetErrors(): void
    {
        $knockRequest = $this->knockRequest;
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $this->assertEquals( [], $knockRequest->errors );

        $errorKey = 'errorKey';
        $errorText = 'errorText';

        $knockRequest->addError( $errorText, $errorKey );

        $this->assertEquals( [$errorKey => $errorText], $knockRequest->errors );

        $knockRequest->addError( 'next Error' );

        $this->assertCount( 2, $knockRequest->errors );
    }
}