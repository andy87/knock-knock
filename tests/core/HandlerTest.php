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

use Exception;
use ReflectionClass;
use andy87\knock_knock\lib\{ ContentType, Method };
use andy87\knock_knock\tests\helpers\HandlerExample;
use andy87\knock_knock\core\{ Handler, Request, Response };
use andy87\knock_knock\tests\helpers\{ PostmanEcho, UnitTestCore };
use andy87\knock_knock\interfaces\{ HandlerInterface, RequestInterface, ResponseInterface };
use andy87\knock_knock\exception\{ InvalidHostException, InvalidEndpointException, ParamNotFoundException, ParamUpdateException };
use andy87\knock_knock\exception\{ handler\EventUpdateException, handler\InvalidMethodException, request\InvalidHeaderException, request\StatusNotFoundException };

/**
 * Class HandlerTest
 *
 *  Тесты для методов класса Handler
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox
 *
 * @tag #test #Handler
 */
class HandlerTest extends UnitTestCore
{
    /**
     * Установки
     *
     * @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @tag #test #Handler #setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->getHandler();
    }

    /**
     *  Проверка конструктора
     *      Тест ожидает что будет создан объект/ экземпляр класса Handler
     *
     * Source: @see Handler::__construct()
     *
     * @return void
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testConstructor
     *
     * @tag #test #Handler #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Handler::class, $this->handler );
    }

    /**
     * Проверка работы Singleton
     *      Тест ожидает что метод вернет объект/ экземпляр класса Handler
     *
     * Source: @see Handler::getInstance()
     *
     * @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testGetInstance
     *
     * @tag #test #Handler #get #instance
     */
    public function testGetInstance(): void
    {
        $handler = Handler::getInstance(self::HOST );

        $this->assertInstanceOf(Handler::class, $handler );

        $handler->disableSSL();

        // переназначаем переменную взяв ее из статического метода
        // -> статический метод должен вернуть тот же объект
        $handler = Handler::getInstance();

        $this->assertInstanceOf(Handler::class, $handler );

        $request = $handler->commonRequest;

        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $request->curlOptions );
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $request->curlOptions );
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
     * 
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testValidateHostName
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
     * 
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventInit
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
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testGetter
     *
     * @tag #test #Handler #get
     */
    public function testGetter()
    {
        $handler = $this->handler;
        $this->assertEquals(self::HOST, $handler->host);
        $this->assertInstanceOf(Request::class, $handler->commonRequest);

        $request = $this->getRequest();
        $this->assertInstanceOf(Request::class, $request );

        $handler->setupRequest( $request );
        $this->assertInstanceOf(Request::class, $handler->realRequest );
        $this->assertCount(6, $handler->eventHandlers);

        $handler->addLog('test');
        $this->assertIsArray( $handler->logs );
        $this->assertCount(1, $handler->logs );
    }

    /**
     * Проверка метода возвращающего все геттеры
     *      Ожидается, что метод вернет массив с актуальными значениями свойств
     *
     * Source: @see Handler::getParams()
     *
     * @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testGetParams
     *
     * @tag #test #Handler #get #params
     */
    public function testGetParams()
    {
        $protocol = 'wss';
        $host = 'getParams.Host';
        $apiUrl = "$protocol://$host";

        $requestCommon = [
            RequestInterface::SETUP_METHOD => Method::PUT,
        ];

        $handler = $this->getHandler( $apiUrl, $requestCommon );

        $events = [
            HandlerInterface::EVENT_AFTER_INIT => function() {
                return HandlerInterface::EVENT_AFTER_INIT;
            },
        ];

        $handler->setupEventHandlers($events);

        $requestRealParams = [
            RequestInterface::SETUP_METHOD => Method::POST,
            RequestInterface::SETUP_CURL_INFO => [ 'info' => 'real' ]
        ];

        $requestReal = $handler->constructRequest(Method::POST, '/endpointReal', $requestRealParams );

        $handler->setupRequest( $requestReal );

        $params = $handler->getParams();

        $this->assertArrayHasKey(HandlerInterface::PARAM_HOST, $params );
        $this->assertEquals( $host, $params[HandlerInterface::PARAM_HOST] );
        $this->assertEquals( $protocol, $requestReal->protocol );

        $this->assertArrayHasKey(HandlerInterface::PARAM_COMMON_REQUEST, $params );
        $this->assertInstanceOf(Request::class, $params[HandlerInterface::PARAM_COMMON_REQUEST] );

        $this->assertArrayHasKey(HandlerInterface::PARAM_REAL_REQUEST, $params );
        $this->assertInstanceOf(Request::class, $params[HandlerInterface::PARAM_REAL_REQUEST] );

        $this->assertArrayHasKey(HandlerInterface::PARAM_EVENT_HANDLERS, $params );
        $this->assertSameSize($events, $params[HandlerInterface::PARAM_EVENT_HANDLERS]);
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
     * @throws EventUpdateException|InvalidEndpointException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testConstruct
     *
     * @tag #test #Handler #construct
     */
    public function testConstruct():void
    {
        $handler = $this->handler;

            $this->assertEquals(self::HOST, $handler->host);

        $handler->on(HandlerInterface::EVENT_CONSTRUCT_REQUEST, function(Handler $handler) {
            $handler->addLog(HandlerInterface::EVENT_CONSTRUCT_REQUEST);
        });
        $handler->on(HandlerInterface::EVENT_CONSTRUCT_RESPONSE, function(Handler $handler) {
            $handler->addLog(HandlerInterface::EVENT_CONSTRUCT_RESPONSE);
        });

        $request = $handler->constructRequest( Method::GET, self::ENDPOINT );
        $this->assertInstanceOf(Request::class, $request );
        $this->assertTrue(in_array(HandlerInterface::EVENT_CONSTRUCT_REQUEST, $handler->logs));

        $response = $handler->constructResponse([
            ResponseInterface::CONTENT => 'content',
            ResponseInterface::HTTP_CODE => 200,
        ], $request );

        $this->assertInstanceOf(Response::class, $response );

        $this->assertTrue(in_array(HandlerInterface::EVENT_CONSTRUCT_RESPONSE, $handler->logs));
    }

    /**
     * Проверка что задаётся свойство `realRequest`
     *      Ожидается что метод задаст свойство `realRequest` объектом класса `Request`
     *
     * Source: @see Handler::setupRequest()
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSetupRequest
     *
     * @tag #test #Handler #setup #request
     */
    public function testSetupRequest()
    {
        $handler = $this->handler;

        $request = $this->getRequest(null, [
            RequestInterface::SETUP_DATA => self::DATA_A
        ]);

        $handler->setupRequest( $request );
        $this->assertInstanceOf( Request::class, $handler->realRequest );
        $this->assertEquals( json_encode(self::DATA_A), json_encode($handler->realRequest->data) );

        // Проверка с перезаписью и добавлением свойств
        $handler->setupRequest( $request, [
            RequestInterface::SETUP_DATA => self::DATA_B,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::RAR
        ]);

        $this->assertEquals( json_encode(self::DATA_B), json_encode($handler->realRequest->data) );
        $this->assertEquals( ContentType::RAR, $handler->realRequest->contentType );
    }

    /**
     * Ожидается что метод задаст свойство `events` массивом с колбеками
     *
     * Source: @see Handler::setupEventHandlers()
     *
     * @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSetupEventHandlers
     *
     * @tag #test #Handler #setup #eventHandlers
     */
    public function testSetupEventHandlers()
    {
        $handler = $this->getHandler();

        $handler->setupEventHandlers([]);

        $this->assertIsArray( $handler->eventHandlers );
        $this->assertCount(0, $handler->eventHandlers);

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

        $callBackList = $handler->setupEventHandlers($eventList);

        $this->assertSameSize( $callBackList, $eventList );

        $this->assertIsArray( $handler->eventHandlers );
        $this->assertSameSize( $eventList, $handler->eventHandlers );
    }

    /**
     * Ожидается что метод добавит callBack в массив `events`
     *
     * Source: @see Handler::on()
     *
     * @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventsOn
     *
     * @tag #test #Handler #event #on
     */
    public function testEventsOn()
    {
        $handlerExample = $this->getHandlerExample();

        $handlerExample->on(HandlerExample::MY_EVENT, function() {});

        $this->assertArrayHasKey(HandlerExample::MY_EVENT, $handlerExample->eventHandlers );
    }

    /**
     * Ожидается что метод event приватный
     *
     * Source: @see Handler::event()
     *
     * @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventCall
     *
     * @tag #test #Handler #event #call
     */
    public function testEventCall()
    {
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('event');

        $this->assertTrue($method->isPublic());

        $handlerExample = $this->getHandlerExample();

        $handlerExample->on(HandlerExample::MY_EVENT, function(Handler $handler) {
            $handler->addLog(HandlerExample::MY_EVENT);
        });

        /** Проверка на вызов `event()` через `callEventHandler` */
        $handlerExample->callEventHandler(HandlerExample::MY_EVENT);

        $this->assertCount(1, $handlerExample->logs, "Ожидается что после вызова `callEventHandler` в лог запишутся данные " );
        $this->assertEquals(HandlerExample::MY_EVENT, $handlerExample->logs[0], "Ожидается что значение в `logs[0]` будет равно значению `HandlerExample::MY_EVENT` " );
    }

    /**
     * Тест ожидает что после вызова метода `off` callBack не будет вызван
     *
     * Source: @see Handler::off()
     *
     * @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventOff
     *
     * @tag #test #Handler #event #off
     */
    public function testEventOff()
    {
        $handlerExample = $this->getHandlerExample();

        $handlerExample->on(HandlerExample::MY_EVENT,
            function( Handler $handler ) {
                $handler->addLog(HandlerExample::MY_EVENT );
            }
        );

        $handlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(1, $handlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT, $handlerExample->logs[0] );

        $handlerExample->off(HandlerExample::MY_EVENT );

        $handlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(1, $handlerExample->logs );
    }

    /**
     * Тест ожидает что после вызова метода `changeEvent` callBack будет изменен
     *
     * Source: @see Handler::changeEvent()
     *
     * @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventChange
     *
     * @tag #test #Handler #event #change
     */
    public function testEventChange()
    {
        $handlerExample = $this->getHandlerExample();

        $handlerExample->on(HandlerExample::MY_EVENT,
            function( Handler $handler ) {
                $handler->addLog(HandlerExample::MY_EVENT );
            }
        );

        $handlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(1, $handlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT, $handlerExample->logs[0] );


        $this->expectException(Exception::class);

        $handlerExample->on(HandlerExample::MY_EVENT,
            function( Handler $handler ) {
            $handler->addLog(HandlerExample::MY_EVENT );
            $handler->addLog(HandlerExample::MY_EVENT );
            $handler->addLog(HandlerExample::MY_EVENT );
        });

        $handlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(2, $handlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT, $handlerExample->logs[1] );

        $handlerExample->changeEvent(HandlerExample::MY_EVENT,
            function( Handler $handler ) {
                $handler->addLog(HandlerExample::MY_EVENT . '3' );
                $handler->addLog(HandlerExample::MY_EVENT . '4' );
            });

        $handlerExample->callEventHandler(HandlerExample::MY_EVENT );

        $this->assertCount(4, $handlerExample->logs );
        $this->assertEquals(HandlerExample::MY_EVENT . '3', $handlerExample->logs[3] );
        $this->assertEquals(HandlerExample::MY_EVENT . '4', $handlerExample->logs[4] );

    }

    /**
     * Ожидается что метод, задаст значения false и 0
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @see Handler::disableSSL()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testDisableSsl
     *
     * @tag #test #Handler #ssl #disable
     */
    public function testDisableSsl()
    {
        $handler = $this->getHandler();

        $handler->disableSSL();

        $request = $handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYPEER] === false );
        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYHOST] === 0 );
    }

    /**
     * Ожидается что метод, задаст значения true и 2
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @see Handler::enableSSL()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEnableSsl
     *
     * @tag #test #Handler #ssl #enable
     */
    public function testEnableSsl()
    {
        $handler = $this->getHandler();

        $handler->disableSSL();
        $handler->enableSSL();

        $request = $handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYPEER] === true );
        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYHOST] === 2 );
    }

    /**
     * Ожидается что метод, задаст значение true для `CURLOPT_FOLLOWLOCATION` в запросе
     *
     * Source: @see Handler::enableRedirect()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEnableRedirect
     *
     * @tag #test #Handler #redirect #enable
     */
    public function testEnableRedirect()
    {
        $handler = $this->getHandler();

        $handler->enableRedirect();

        $request = $handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_FOLLOWLOCATION] === true );
    }

    /**
     * Ожидается что метод, задаст значения для `CURLOPT_COOKIE`, `CURLOPT_COOKIEJAR` и `CURLOPT_COOKIEFILE` в запросе
     *
     * Source: @see Handler::UseCookie()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUseCookie
     *
     * @tag #test #Handler #cookie
     */
    public function testUseCookie()
    {
        $handler = $this->getHandler();

        $cookie = 'cookie=cookie';
        $jar = 'jar.txt';
        $file = 'file.txt';

        $handler->useCookie( $cookie, $jar );

        $request = $handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEFILE] === $jar );

        $handler->useCookie( $cookie, $jar, $file );

        $request = $handler->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEFILE] === $file );
    }

    /**
     * Ожидается что метод `send` вернет объект класса `Response` с заданными свойствами
     * и что в свойстве `content` будет содержимое ответа
     *
     * Source: @see Handler::send()
     * Source: @see Handler::SendRequest()
     * Source: @see Handler::getResponseOnSendCurlRequest()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSendRequest
     *
     * @tag #test #Handler #send
     */
    public function testSendRequest()
    {
        $handler = PostmanEcho::getHandlerInstance();

        $request = PostmanEcho::constructRequestMethodGet([
            RequestInterface::SETUP_DATA => PostmanEcho::DATA
        ]);

        $response = $handler->send($request);

        $content = json_decode( $response->content, true );

        $this->assertArrayHasKey(PostmanEcho::GET_KEY_ARGS, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_HEADERS, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_URL, $content);

        $this->assertEquals( PostmanEcho::DATA, $content[PostmanEcho::GET_KEY_ARGS] );

        $this->assertEquals( $response->request->url, $content[PostmanEcho::GET_KEY_URL] );
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
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSendRequestWithFakeResponse
     *
     * @tag #test #Handler #send #fakeResponse
     */
    public function testSendRequestWithFakeResponse(): void
    {
        $handler = PostmanEcho::getHandlerInstance();
        $this->assertInstanceOf(Handler::class, $handler );

        $request = PostmanEcho::constructRequestMethodGet();
        $this->assertInstanceOf(Request::class, $request );

        $fakeResponse = [
            ResponseInterface::CONTENT => json_encode(PostmanEcho::DATA),
            ResponseInterface::HTTP_CODE => 777,
        ];
        $request->setFakeResponse($fakeResponse);

        $response = $handler->send( $request );
        $this->assertInstanceOf(Response::class, $response );

        $this->assertEquals($fakeResponse[ ResponseInterface::CONTENT ], $response->content );
        $this->assertEquals($fakeResponse[ ResponseInterface::HTTP_CODE ], $response->httpCode );
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
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSendRequestOnMethodPost
     *
     * @tag #test #Handler #send #post
     */
    public function testSendRequestOnMethodPost(): void
    {
        $handler = PostmanEcho::getHandlerInstance();
        $this->assertInstanceOf(Handler::class, $handler );

        $data = PostmanEcho::DATA;

        $request = PostmanEcho::constructRequestMethodPost([
            RequestInterface::SETUP_DATA => $data,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON
        ]);
        $this->assertInstanceOf(Request::class, $request );

        $response = $handler->send($request);

        $this->assertInstanceOf(Response::class, $response );

        $content = json_decode( $response->content, true );

        /** @see PostmanEcho::ENDPOINT_POST */
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_ARGS, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_DATA, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_FILES, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_FORM, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_HEADERS, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_JSON, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_URL, $content);

        $this->assertEquals( $response->request->url, $content[PostmanEcho::GET_KEY_URL] );
        $this->assertEquals( Method::POST, $response->request->method );
    }



    /**
     * Ожидается что метод `updateRequestParams` обновит параметры запроса.
     * Вызов метода `updateRequestParams` с новыми параметрами произойдет внутри метода `setupRequest`
     *
     * Source: @see Handler::updateRequestParams()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUpdateRequestParams
     *
     * @tag #test #Handler #update #requestParams
     */
    public function testUpdateRequestParams()
    {
        $handler = $this->getHandler(self::HOST );
        $this->assertInstanceOf(Handler::class, $handler );

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


        $request = $handler->constructRequest( Method::GET, '/tyda', $oldData );
        $this->assertInstanceOf(Request::class, $request );

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

        $request = $handler->setupRequest( $request, $newData )->realRequest;

        $this->assertEquals( $newData[RequestInterface::SETUP_PROTOCOL], $request->protocol );
        $this->assertEquals( $newData[RequestInterface::SETUP_HOST], $request->host );
        $this->assertEquals( $newData[RequestInterface::SETUP_METHOD], $request->method );
        $this->assertEquals( $newData[RequestInterface::SETUP_HEADERS]['Content-Type'], $request->headers['Content-Type'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_DATA]['state'], $request->data['state'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CURL_OPTIONS][CURLOPT_HEADER], $request->curlOptions[CURLOPT_HEADER] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CURL_INFO]['info'], $request->curlInfo['info'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CONTENT_TYPE], $request->contentType );
    }

    /**
     * Ожидается что метод `updatePostFields` задаст `CURLOPT_POSTFIELDS` свойство запроса
     *
     * Source: @see Handler::updatePostFields()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUpdatePostFields
     *
     * @tag #test #Handler #update #postFields
     */
    public function testUpdatePostFields()
    {
        $handler = $this->getHandler(self::HOST, []);
        $this->assertInstanceOf(Handler::class, $handler );

        $postFields = [
            'a' => 1,
            'b' => 2,
        ];

        $request = $handler->constructRequest(Method::POST,self::ENDPOINT, [
            RequestInterface::SETUP_DATA => $postFields,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON
        ]);

        $response = $handler->send(
            $request->setFakeResponse([
                ResponseInterface::CONTENT => self::CONTENT,
                ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
            ])
        );

        $responsePostFields = $response->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( json_encode($postFields), $responsePostFields );

        $request = $handler->constructRequest(Method::PUT,self::ENDPOINT, [
            RequestInterface::SETUP_DATA => $postFields,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::FORM
        ]);

        $response = $handler->send(
            $request->setFakeResponse([
                ResponseInterface::CONTENT => self::CONTENT,
                ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
            ])
        );

        $responsePostFields = $response->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( http_build_query($postFields), $responsePostFields );
    }

    /**
     * Ожидается что метод `updateMethod` задаст `CURLOPT_CUSTOMREQUEST` свойство запроса
     *
     * @dataProvider methodListProvider
     *
     * Source: @see Handler::updateMethod()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUpdateMethod
     *
     * @tag #test #Handler #update #method
     */
    public function testUpdateMethod( string $method )
    {
        $handler = $this->getHandler(self::HOST, []);

        $fakeResponse = [
            ResponseInterface::CONTENT => self::CONTENT,
            ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ];

        $request = $handler->constructRequest( $method,self::ENDPOINT );

        $request->setFakeResponse($fakeResponse);

        $response = $handler->send( $request );

        $this->assertEquals( $method, $response->request->method );
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
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testValidateMethod
     *
     * @tag #test #Handler #validate #method
     */
    public function testValidateMethod( string $method )
    {
        $handler = new Handler(self::HOST);

        $request = $handler->constructRequest( $method,self::ENDPOINT );

        $handler->setupRequest( $request );

        $this->assertEquals( $method, $handler->realRequest->method );

        $this->expectException(Exception::class);

        $handler->constructRequest( 'INVALID_METHOD',self::ENDPOINT );
    }

    /**
     * Возвращает список методов из класса `Method`
     *
     * @return string[]
     *
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
     * 
     *
     * @tag #test #Handler #example
     */
    private function getHandlerExample(): HandlerExample
    {
        return new HandlerExample(self::HOST );
    }
}