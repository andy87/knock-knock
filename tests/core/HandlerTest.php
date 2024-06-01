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
use andy87\knock_knock\interfaces\{HandlerInterface, RequestInterface, ResponseInterface};
use andy87\knock_knock\lib\{ContentType, Method};
use andy87\knock_knock\tests\helpers\{PostmanEcho, UnitTestCore};
use andy87\knock_knock\tests\helpers\HandlerExample;
use Exception;
use ReflectionClass;

/**
 * Class HandlerTest
 *
 *  Тесты для методов класса Handler
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox
 *
 * @tag #test #Handler
 */
class HandlerTest extends UnitTestCore
{
    /** @var Handler $Handler */
    private Handler $Handler;



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

        $this->Handler = $this->getHandler();
    }

    /**
     *  Проверка конструктора
     *      Тест ожидает что будет создан объект/ экземпляр класса Handler
     *
     * Source: @see Handler::__construct()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testConstructor
     *
     * @tag #test #Handler #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Handler::class, $this->Handler );
    }

    /**
     * Проверка работы Singleton
     *      Тест ожидает что метод вернет объект/ экземпляр класса Handler
     *
     * Source: @see Handler::getInstance()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testGetInstance
     *
     * @tag #test #Handler #get #instance
     */
    public function testGetInstance(): void
    {
        $Handler = Handler::getInstance(self::HOST );

        $this->assertInstanceOf(Handler::class, $Handler );

        $Handler->disableSSL();

        // переназначаем переменную взяв ее из статического метода
        // -> статический метод должен вернуть тот же объект
        $Handler = Handler::getInstance();

        $this->assertInstanceOf(Handler::class, $Handler );

        $Request = $Handler->commonRequest;

        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $Request->curlOptions );
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $Request->curlOptions );
    }

    /**
     *  Проверка работы валидации имени хоста
     *      Ожидается, что метод вернет true если переданное имя хоста валидно
     *
     * Source: @see Handler::validateHostName()
     *
     * @dataProvider hostNameProvider
     *
     * @param string $host
     * @param bool $expected
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testValidateHostName
     *
     * @tag #test #Handler #validate #hostName
     */
    public function testValidateHostName( string $host, bool $expected ): void
    {
        $result = Handler::validateHostName($host);

        $this->assertEquals( $expected, $result );
    }

    /**
     * Данные для теста `testValidateHostName`
     *
     * Data: @see HandlerTest::testValidateHostName()
     *
     * @return array[]
     *
     * @tag #test #Handler #provider #validate #hostName
     */
    public static function hostNameProvider(): array
    {
        return [
            ['example.com', true], // 0 +
            ['subdomain.example.com', true], // 1 +
            ['-example.com', false], // 2 неверный
            ['example-.com', false], // 3 неверный
            ['exa_mple.com', false], // 4 неверный
            ['example..com', false], // 5 неверный
            ['example.c', false], // 6 неверный, tld слишком короткий
            ['ex' . str_repeat('a', 250) . 'mple.com', false], // 7 неверный, слишком длинный
            ['valid-domain.com', true], // 8
            ['sub.valid-domain.com', true], // 9
            ['sub.sub.valid-domain.com', true], // 10
            ['invalid_domain.com', false], // 11 неверный
            ['invalid--domain.com', false], // 12 неверный
            ['invalid.domain-.com', false], // 13 неверный
            ['.invalid.domain.com', false], // 14 неверный
            ['valid-domain.co.uk', true], // 15 +
        ];
    }

    /**
     * Проверка работы события INIT вызывающегося после __construct
     *      Ожидается что `Handler` выполнит метод init().
     *
     * Source: @see Handler::init()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEventInit
     *
     * @tag #test #Handler #event #init
     */
    public function testEventInit()
    {
        $this->getHandlerExample();

        $this->assertEquals(HandlerExample::INIT_DONE, HandlerExample::$initResult );
    }

    /**
     * Проверка работы геттеров
     *      Ожидается, что метод вернет значение свойств
     *
     * Source: @see Handler::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testGetter
     *
     * @tag #test #Handler #get
     */
    public function testGetter()
    {
        $Handler = $this->Handler;
        $this->assertEquals(self::HOST, $Handler->host);
        $this->assertInstanceOf(Request::class, $Handler->commonRequest);

        $Request = $this->getRequest();
        $this->assertInstanceOf(Request::class, $Request );

        $Handler->setupRequest( $Request );
        $this->assertInstanceOf(Request::class, $Handler->realRequest );
        $this->assertCount(6, $Handler->eventHandlers);

        $Handler->addLog('test');
        $this->assertIsArray( $Handler->logs );
        $this->assertCount(1, $Handler->logs );
    }

    /**
     * Проверка метода возвращающего все геттеры
     *      Ожидается, что метод вернет массив с актуальными значениями свойств
     *
     * Source: @see Handler::getParams()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testGetParams
     *
     * @tag #test #Handler #get #params
     */
    public function testGetParams()
    {
        $protocol = 'wss';
        $host = 'getParams.Host';
        $apiUrl = "$protocol://$host";

        $RequestCommon = [
            RequestInterface::SETUP_METHOD => Method::PUT,
        ];

        $Handler = $this->getHandler( $apiUrl, $RequestCommon );

        $events = [
            HandlerInterface::EVENT_AFTER_INIT => function() {
                return HandlerInterface::EVENT_AFTER_INIT;
            },
        ];

        $Handler->setupEventHandlers($events);

        $RequestRealParams = [
            RequestInterface::SETUP_METHOD => Method::POST,
            RequestInterface::SETUP_CURL_INFO => [ 'info' => 'real' ]
        ];

        $RequestReal = $Handler->constructRequest(Method::POST, '/endpointReal', $RequestRealParams );

        $Handler->setupRequest( $RequestReal );

        $params = $Handler->getParams();

        $this->assertArrayHasKey('host', $params );
        $this->assertEquals( $host, $params['host'] );
        $this->assertEquals( $protocol, $RequestReal->protocol );

        $this->assertArrayHasKey('commonRequest', $params );
        $this->assertInstanceOf(Request::class, $params['commonRequest'] );

        $this->assertArrayHasKey('realRequest', $params );
        $this->assertInstanceOf(Request::class, $params['realRequest'] );

        $this->assertArrayHasKey('events', $params );
        $this->assertSameSize($events, $params['events']);
    }

    /**
     * Проверка методов отвечающихз за создание объектов `Request` и `Response`
     *      Ожидается, что методы `construct` вернут объекты классов `Request` и `Response`
     *      Ожидается, что сработают эвенты `EVENT_CONSTRUCT_REQUEST` и `EVENT_CONSTRUCT_RESPONSE` и в лог запишутся данные
     *
     * Source: @see Handler::constructRequest()
     * Source: @see Handler::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testConstruct
     *
     * @tag #test #Handler #construct
     */
    public function testConstruct():void
    {
        $Handler = $this->Handler;

            $this->assertEquals(self::HOST, $Handler->host);

        $Handler->on(HandlerInterface::EVENT_CONSTRUCT_REQUEST, function(Handler $Handler) {
            $Handler->addLog(HandlerInterface::EVENT_CONSTRUCT_REQUEST);
        });
        $Handler->on(HandlerInterface::EVENT_CONSTRUCT_RESPONSE, function(Handler $Handler) {
            $Handler->addLog(HandlerInterface::EVENT_CONSTRUCT_RESPONSE);
        });

        $Request = $Handler->constructRequest( Method::GET, self::ENDPOINT );
        $this->assertInstanceOf(Request::class, $Request );
        $this->assertTrue(in_array(HandlerInterface::EVENT_CONSTRUCT_REQUEST, $Handler->logs));

        $Response = $Handler->constructResponse([
            ResponseInterface::CONTENT => 'content',
            ResponseInterface::HTTP_CODE => 200,
        ], $Request );

        $this->assertInstanceOf(Response::class, $Response );

        $this->assertTrue(in_array(HandlerInterface::EVENT_CONSTRUCT_RESPONSE, $Handler->logs));
    }

    /**
     * Проверка что задаётся свойство `realRequest`
     *      Ожидается что метод задаст свойство `realRequest` объектом класса `Request`
     *
     * Source: @see Handler::setupRequest()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testSetupRequest
     *
     * @tag #test #Handler #setup #request
     */
    public function testSetupRequest()
    {
        $Handler = $this->Handler;

        $Request = $this->getRequest(null, [
            RequestInterface::SETUP_DATA => self::DATA_A
        ]);

        $Handler->setupRequest( $Request );
        $this->assertInstanceOf( Request::class, $Handler->realRequest );
        $this->assertEquals( json_encode(self::DATA_A), json_encode($Handler->realRequest->data) );

        // Проверка с перезаписью и добавлением свойств
        $Handler->setupRequest( $Request, [
            RequestInterface::SETUP_DATA => self::DATA_B,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::RAR
        ]);

        $this->assertEquals( json_encode(self::DATA_B), json_encode($Handler->realRequest->data) );
        $this->assertEquals( ContentType::RAR, $Handler->realRequest->contentType );
    }

    /**
     * Ожидается что метод задаст свойство `events` массивом с колбеками
     *
     * Source: @see Handler::setupEventHandlers()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testSetupEventHandlers
     *
     * @tag #test #Handler #setup #eventHandlers
     */
    public function testSetupEventHandlers()
    {
        $Handler = $this->getHandler();

        $Handler->setupEventHandlers([]);

        $this->assertIsArray( $Handler->eventHandlers );
        $this->assertCount(0, $Handler->eventHandlers);

        $eventList = [
            HandlerInterface::EVENT_AFTER_INIT => function() {
                return HandlerInterface::EVENT_AFTER_INIT;
            },
            HandlerInterface::EVENT_CONSTRUCT_REQUEST => function() {
                return HandlerInterface::EVENT_CONSTRUCT_REQUEST;
            },
            HandlerInterface::EVENT_CONSTRUCT_RESPONSE => function() {
                return HandlerInterface::EVENT_CONSTRUCT_RESPONSE;
            },
        ];

        $callBackList = $Handler->setupEventHandlers($eventList);

        $this->assertSameSize( $callBackList, $eventList );

        $this->assertIsArray( $Handler->eventHandlers );
        $this->assertSameSize( $eventList, $Handler->eventHandlers );
    }

    /**
     * Ожидается что метод добавит callBack в массив `events`
     *
     * Source: @see Handler::on()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEventsOn
     *
     * @tag #test #Handler #event #on
     */
    public function testEventsOn()
    {
        $HandlerExample = $this->getHandlerExample();

        $HandlerExample->on(HandlerExample::MY_EVENT, function() {});

        $this->assertArrayHasKey(HandlerExample::MY_EVENT, $HandlerExample->eventHandlers );
    }

    /**
     * Ожидается что метод event приватный
     *
     * Source: @see Handler::event()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEventCall
     *
     * @tag #test #Handler #event #call
     */
    public function testEventCall()
    {
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('event');

        // проверка на приватность метода
        $this->assertTrue($method->isPrivate());

        $HandlerExample = $this->getHandlerExample();

        $HandlerExample->on(HandlerExample::MY_EVENT, function(Handler $Handler) {
            $Handler->addLog(HandlerExample::MY_EVENT);
        });

        /** Проверка на вызов `event()` через `callEventHandler` */
        $HandlerExample->callEventHandler(HandlerExample::MY_EVENT);

        $this->assertCount(1, $HandlerExample->logs, "Ожидается что после вызова `callEventHandler` в лог запишутся данные " );
        $this->assertEquals(HandlerExample::MY_EVENT, $HandlerExample->logs[0], "Ожидается что значение в `logs[0]` будет равно значению `HandlerExample::MY_EVENT` " );
    }

    /**
     * Тест ожидает что после вызова метода `off` callBack не будет вызван
     *
     * Source: @see Handler::off()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEventOff
     *
     * @tag #test #Handler #event #off
     */
    public function testEventOff()
    {
        $HandlerExample = $this->getHandlerExample();

        $HandlerExample->on(HandlerExample::MY_EVENT,
            function( Handler $Handler ) {
                $Handler->addLog(HandlerExample::MY_EVENT );
            }
        );

        $HandlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(1, $HandlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT, $HandlerExample->logs[0] );

        $HandlerExample->off(HandlerExample::MY_EVENT );

        $HandlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(1, $HandlerExample->logs );
    }

    /**
     * Тест ожидает что после вызова метода `changeEvent` callBack будет изменен
     *
     * Source: @see Handler::changeEvent()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEventChange
     *
     * @tag #test #Handler #event #change
     */
    public function testEventChange()
    {
        $HandlerExample = $this->getHandlerExample();

        $HandlerExample->on(HandlerExample::MY_EVENT,
            function( Handler $Handler ) {
                $Handler->addLog(HandlerExample::MY_EVENT );
            }
        );

        $HandlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(1, $HandlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT, $HandlerExample->logs[0] );


        $this->expectException(Exception::class);

        $HandlerExample->on(HandlerExample::MY_EVENT,
            function( Handler $Handler ) {
            $Handler->addLog(HandlerExample::MY_EVENT );
            $Handler->addLog(HandlerExample::MY_EVENT );
            $Handler->addLog(HandlerExample::MY_EVENT );
        });

        $HandlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(2, $HandlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT, $HandlerExample->logs[1] );

        $HandlerExample->changeEvent(HandlerExample::MY_EVENT,
            function( Handler $Handler ) {
                $Handler->addLog(HandlerExample::MY_EVENT . '3' );
                $Handler->addLog(HandlerExample::MY_EVENT . '4' );
            });

        $HandlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(4, $HandlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT . '3', $HandlerExample->logs[3] );
        $this->assertEquals(HandlerExample::MY_EVENT . '4', $HandlerExample->logs[4] );

    }

    /**
     * Ожидается что метод, задаст значения false и 0
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @see Handler::disableSSL()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testDisableSsl
     *
     * @tag #test #Handler #ssl #disable
     */
    public function testDisableSsl()
    {
        $Handler = $this->getHandler();

        $Handler->disableSSL();

        $Request = $Handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $Request->curlOptions[CURLOPT_SSL_VERIFYPEER] === false );
        $this->assertTrue( $Request->curlOptions[CURLOPT_SSL_VERIFYHOST] === 0 );
    }

    /**
     * Ожидается что метод, задаст значения true и 2
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @see Handler::enableSSL()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEnableSsl
     *
     * @tag #test #Handler #ssl #enable
     */
    public function testEnableSsl()
    {
        $Handler = $this->getHandler();

        $Handler->disableSSL();
        $Handler->enableSSL();

        $Request = $Handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $Request->curlOptions[CURLOPT_SSL_VERIFYPEER] === true );
        $this->assertTrue( $Request->curlOptions[CURLOPT_SSL_VERIFYHOST] === 2 );
    }

    /**
     * Ожидается что метод, задаст значение true для `CURLOPT_FOLLOWLOCATION` в запросе
     *
     * Source: @see Handler::enableRedirect()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testEnableRedirect
     *
     * @tag #test #Handler #redirect #enable
     */
    public function testEnableRedirect()
    {
        $Handler = $this->getHandler();

        $Handler->enableRedirect();

        $Request = $Handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $Request->curlOptions[CURLOPT_FOLLOWLOCATION] === true );
    }

    /**
     * Ожидается что метод, задаст значения для `CURLOPT_COOKIE`, `CURLOPT_COOKIEJAR` и `CURLOPT_COOKIEFILE` в запросе
     *
     * Source: @see Handler::UseCookie()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testUseCookie
     *
     * @tag #test #Handler #cookie
     */
    public function testUseCookie()
    {
        $Handler = $this->getHandler();

        $cookie = 'cookie=cookie';
        $jar = 'jar.txt';
        $file = 'file.txt';

        $Handler->useCookie( $cookie, $jar );

        $Request = $Handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $Request->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $Request->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $Request->curlOptions[CURLOPT_COOKIEFILE] === $jar );

        $Handler->useCookie( $cookie, $jar, $file );

        $Request = $Handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $Request->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $Request->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $Request->curlOptions[CURLOPT_COOKIEFILE] === $file );
    }

    /**
     * Ожидается что метод `send` вернет объект класса `Response` с заданными свойствами
     * и что в свойстве `content` будет содержимое ответа
     *
     * Source: @see Handler::send()
     * Source: @see Handler::SendRequest()
     * Source: @see Handler::getResponseOnSendCurlRequest()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testSendRequest
     *
     * @tag #test #Handler #send
     */
    public function testSendRequest()
    {
        $Handler = PostmanEcho::getHandlerInstance();

        $Request = PostmanEcho::constructRequestMethodGet([
            RequestInterface::SETUP_DATA => PostmanEcho::DATA
        ]);

        $Response = $Handler->setupRequest( $Request )->send();

        $response = json_decode( $Response->content, true );

        $this->assertArrayHasKey('args', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('url', $response);

        $this->assertEquals( PostmanEcho::DATA, $response['args'] );

        $this->assertEquals( $Response->request->url, $response['url'] );
    }

    /**
     * Ожидается что метод `send` вернет объект класса `Response` с заданными фейковыми свойствами
     *
     * Source: @see Handler::send()
     * Source: @see Handler::SendRequest()
     * Source: @see Handler::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testSendRequestWithFakeResponse
     *
     * @tag #test #Handler #send #fakeResponse
     */
    public function testSendRequestWithFakeResponse(): void
    {
        $Handler = PostmanEcho::getHandlerInstance();
        $this->assertInstanceOf(Handler::class, $Handler );

        $Request = PostmanEcho::constructRequestMethodGet();
        $this->assertInstanceOf(Request::class, $Request );

        $fakeResponse = [
            ResponseInterface::CONTENT => json_encode(PostmanEcho::DATA),
            ResponseInterface::HTTP_CODE => 777,
        ];

        $Response = $Handler->setupRequest( $Request )->send( $fakeResponse );
        $this->assertInstanceOf(Response::class, $Response );

        $this->assertEquals($fakeResponse[ ResponseInterface::CONTENT ], $Response->content );
        $this->assertEquals($fakeResponse[ ResponseInterface::HTTP_CODE ], $Response->httpCode );
    }

    /**
     * Ожидается что метод `send` вернет `Response` ответ на запрос методом `POST`
     *
     * Source: @see Handler::send()
     * Source: @see Handler::SendRequest()
     * Source: @see Handler::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testSendRequestOnMethodPost
     *
     * @tag #test #Handler #send #post
     */
    public function testSendRequestOnMethodPost(): void
    {
        $Handler = PostmanEcho::getHandlerInstance();
        $this->assertInstanceOf(Handler::class, $Handler );

        $data = PostmanEcho::DATA;

        $Request = PostmanEcho::constructRequestMethodPost([
            RequestInterface::SETUP_DATA => $data,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON
        ]);
        $this->assertInstanceOf(Request::class, $Request );

        $Response = $Handler->setupRequest( $Request )->send();

        $this->assertInstanceOf(Response::class, $Response );

        $response = json_decode( $Response->content, true );

        /** @see PostmanEcho::ENDPOINT_POST */
        $this->assertArrayHasKey('args', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('files', $response);
        $this->assertArrayHasKey('form', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('json', $response);
        $this->assertArrayHasKey('url', $response);

        $this->assertEquals( $Response->request->url, $response['url'] );
        $this->assertEquals( Method::POST, $Response->request->method );
    }



    /**
     * Ожидается что метод `updateRequestParams` обновит параметры запроса.
     * Вызов метода `updateRequestParams` с новыми параметрами произойдет внутри метода `setupRequest`
     *
     * Source: @see Handler::updateRequestParams()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testUpdateRequestParams
     *
     * @tag #test #Handler #update #requestParams
     */
    public function testUpdateRequestParams()
    {
        $Handler = $this->getHandler(self::HOST );
        $this->assertInstanceOf(Handler::class, $Handler );

        $oldData = [
            RequestInterface::SETUP_PROTOCOL => Request::PROTOCOL_HTTP,
            RequestInterface::SETUP_HOST => self::HOST,
            RequestInterface::SETUP_METHOD => Method::GET,
            RequestInterface::SETUP_HEADERS => [
                'Content-Type' => ContentType::JSON,
            ],
            RequestInterface::SETUP_DATA => ['state' => 'old'],
            RequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => false,
            ],
            RequestInterface::SETUP_CURL_INFO => [
                'info' => 'old',
            ],
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON,
        ];


        $Request = $Handler->constructRequest( Method::GET, '/tyda', $oldData );
        $this->assertInstanceOf(Request::class, $Request );

        $newData = [
            RequestInterface::SETUP_PROTOCOL => Request::PROTOCOL_HTTPS,
            RequestInterface::SETUP_HOST => self::HOST . '/new',
            RequestInterface::SETUP_METHOD => Method::POST,
            RequestInterface::SETUP_HEADERS => [
                'Content-Type' => ContentType::FORM,
            ],
            RequestInterface::SETUP_DATA => ['state' => 'old'],
            RequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => true,
            ],
            RequestInterface::SETUP_CURL_INFO => [
                'info' => 'new',
            ],
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::XML,
        ];

        $Request = $Handler->setupRequest( $Request, $newData )->realRequest;

        $this->assertEquals( $newData[RequestInterface::SETUP_PROTOCOL], $Request->protocol );
        $this->assertEquals( $newData[RequestInterface::SETUP_HOST], $Request->host );
        $this->assertEquals( $newData[RequestInterface::SETUP_METHOD], $Request->method );
        $this->assertEquals( $newData[RequestInterface::SETUP_HEADERS]['Content-Type'], $Request->headers['Content-Type'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_DATA]['state'], $Request->data['state'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CURL_OPTIONS][CURLOPT_HEADER], $Request->curlOptions[CURLOPT_HEADER] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CURL_INFO]['info'], $Request->curlInfo['info'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CONTENT_TYPE], $Request->contentType );
    }

    /**
     * Ожидается что метод `updatePostFields` задаст `CURLOPT_POSTFIELDS` свойство запроса
     *
     * Source: @see Handler::updatePostFields()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testUpdatePostFields
     *
     * @tag #test #Handler #update #postFields
     */
    public function testUpdatePostFields()
    {
        $Handler = $this->getHandler(self::HOST, []);
        $this->assertInstanceOf(Handler::class, $Handler );

        $postFields = [
            'a' => 1,
            'b' => 2,
        ];

        $Request = $Handler->constructRequest(Method::POST,self::ENDPOINT, [
            RequestInterface::SETUP_DATA => $postFields,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON
        ]);

        $Response = $Handler->setupRequest( $Request )->send([
            ResponseInterface::CONTENT => self::CONTENT,
            ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ]);

        $responsePostFields = $Response->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( json_encode($postFields), $responsePostFields );

        $Request = $Handler->constructRequest(Method::PUT,self::ENDPOINT, [
            RequestInterface::SETUP_DATA => $postFields,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::FORM
        ]);

        $Response = $Handler->setupRequest( $Request )->send([
            ResponseInterface::CONTENT => self::CONTENT,
            ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ]);

        $responsePostFields = $Response->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( http_build_query($postFields), $responsePostFields );
    }

    /**
     * Ожидается что метод `updateMethod` задаст `CURLOPT_CUSTOMREQUEST` свойство запроса
     *
     * @dataProvider methodListProvider
     *
     * Source: @see Handler::updateMethod()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testUpdateMethod
     *
     * @tag #test #Handler #update #method
     */
    public function testUpdateMethod( string $method )
    {
        $Handler = $this->getHandler(self::HOST, []);

        $fakeResponse = [
            ResponseInterface::CONTENT => self::CONTENT,
            ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ];

        $Request = $Handler->constructRequest( $method,self::ENDPOINT );

        $Response = $Handler->setupRequest( $Request )->send( $fakeResponse );

        $this->assertEquals( $method, $Response->request->method );
    }

    /**
     * Проверка правильного определения валидности метода.
     *
     *      Ожидается что метод `validateMethod` вернет true для валидных методов
     *      Проверка происходит через вызов метода `constructRequest`
     *
     * @dataProvider methodListProvider
     *
     * Source: @see Handler::validateMethod()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/HandlerTest.php --testdox --filter testValidateMethod
     *
     * @tag #test #Handler #validate #method
     */
    public function testValidateMethod( string $method )
    {
        $Handler = new Handler(self::HOST);

        $Request = $Handler->constructRequest( $method,self::ENDPOINT );

        $Handler->setupRequest( $Request );

        $this->assertEquals( $method, $Handler->realRequest->method );

        $this->expectException(Exception::class);

        $Handler->constructRequest( 'INVALID_METHOD',self::ENDPOINT );
    }

    /**
     * Возвращает список методов из класса `Method`
     *
     * @return string[]
     */
    public static function methodListProvider(): array
    {
        return [
            [ Method::GET ],
            [ Method::POST ],
            [ Method::PUT ],
            [ Method::DELETE ],
            [ Method::PATCH ],
            [ Method::OPTIONS ],
            [ Method::HEAD ],
            [ Method::TRACE ]
        ];
    }



    // === Private ===

    /**
     * Вспомогательный метод для создания объекта класса `HandlerExample`
     *
     * @return HandlerExample
     *
     * @throws Exception
     *
     * @tag #test #Handler #example
     */
    private function getHandlerExample(): HandlerExample
    {
        return new HandlerExample(self::HOST );
    }
}